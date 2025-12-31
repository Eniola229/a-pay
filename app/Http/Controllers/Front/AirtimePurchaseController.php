<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AirtimePurchase;
use App\Models\Balance;
use App\Models\Logged;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\AirtimePurchaseMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Client\RequestException;
use App\Services\CashbackService;
use App\Services\WebTransactionService;
use Illuminate\Support\Str;

class AirtimePurchaseController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('airtime');
    }

    public function buyAirtime(Request $request)
    {
        // Validate the request
        $request->validate([
            'phone_number' => 'required|string', 
            'amount' => 'required|numeric|min:10|max:50000', 
            'network_id' => 'required|string|in:mtn,airtel,glo,9mobile', 
            'pin' => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $user) {
            
            // -----------------------
            // 1️⃣ Check balance and verify PIN
            // -----------------------
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();

            // Verify the PIN
            if (!Hash::check($request->pin, $balance->pin)) {
                return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
            }

            // Check if the user has sufficient balance
            if (!$balance || $balance->balance < $request->amount) {
                return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
            }

            // -----------------------
            // 2️⃣ Generate unique request ID
            // -----------------------
            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            // -----------------------
            // 3️⃣ Deduct balance via WebTransactionService (DEBIT)
            // -----------------------
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'DEBIT',
                    $request->phone_number,
                    strtoupper($request->network_id) . " airtime purchase for " . $request->phone_number,
                    $requestId,
                    'AIRTIME' // Service type for cashback calculation
                );

                // Refresh balance for display if needed
                $balance->refresh();
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ], 500);
            }

            // -----------------------
            // 4️⃣ Store the airtime purchase record (optional)
            // -----------------------
            // $airtime = AirtimePurchase::create([
            //     'user_id' => $user->id,
            //     'phone_number' => $request->phone_number,
            //     'amount' => $request->amount,
            //     'network_id' => $request->network_id,
            //     'status' => 'PENDING'
            // ]);

            // -----------------------
            // 5️⃣ Prepare the external API request
            // -----------------------
            $apiUrl = 'https://ebills.africa/wp-json/api/v2/airtime';
            $headers = [
                'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                'Content-Type' => 'application/json'
            ];

            $data = [
                'request_id' => $requestId,
                'phone' => $request->phone_number,
                'service_id' => $request->network_id,
                'amount' => $request->amount
            ];

            // -----------------------
            // 6️⃣ Make the API request
            // -----------------------
            try {
                $response = Http::withHeaders($headers)->post($apiUrl, $data);
            } catch (\Exception $e) {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'CREDIT',
                    $request->phone_number,
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId
                );

                // Update original transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $airtime->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t connect to our endpoint. Please check your internet connection and try again.'
                ], 500);
            }

            // -----------------------
            // 7️⃣ Handle the API response
            // -----------------------
            if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
                // Log success
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => 'Airtime purchase successful',
                    'stack_trace' => json_encode($response->json()),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                // Update transaction status to success
                $transaction->update([
                    'status' => 'SUCCESS',
                    'reference' => $requestId
                ]);

                // $airtime->update(['status' => 'SUCCESS']);

                // -----------------------
                // 8️⃣ Apply cashback if not already applied
                // -----------------------
                $cashback = $transaction->cash_back ?? 0;
                
                // Only create a separate cashback transaction if cashback > 0
                if ($cashback > 0) {
                    $cashbackTransaction = $this->transactionService->createTransaction(
                        $user,
                        $cashback,
                        'CREDIT',
                        $request->phone_number,
                        'Cashback for airtime purchase',
                        'CASHBACK_' . $requestId
                    );

                    $cashbackTransaction->update([
                        'status' => 'SUCCESS'
                    ]);
                }

                // Send success email
                Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'SUCCESS'));

                return response()->json([
                    'status' => true, 
                    'message' => 'Airtime purchased successfully',
                    'cashback' => $cashback,
                    'balance' => $balance->fresh()->balance
                ]);

            } else {
                // Log the error response
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => $response->json('message') ?? 'API request failed',
                    'stack_trace' => json_encode($response->json(), JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'CREDIT',
                    $request->phone_number,
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId
                );

                // Update transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $airtime->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'Airtime purchase failed. Your service provider may be unavailable. Please try again later.'
                ], 500);
            }
        });
    }

    public function recentPurchases()
    {
        $purchases = AirtimePurchase::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->select('phone_number')
            ->distinct()
            ->take(5)
            ->get();

        return response()->json($purchases);
    }
}