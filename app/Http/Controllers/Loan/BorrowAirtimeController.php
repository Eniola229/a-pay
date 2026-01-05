<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AirtimePurchase;
use App\Models\Balance;
use App\Models\Logged;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Mail\AirtimePurchaseMail;
use Illuminate\Support\Facades\Mail;
use App\Models\CreditLimit;
use App\Models\Borrow;
use App\Services\WebTransactionService;
use Illuminate\Support\Str;

class BorrowAirtimeController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('borrow_airtime');
    }

    public function buyAirtime(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string', 
            'amount' => 'required|numeric|min:10|max:50000', 
            'network_id' => 'required|string|in:mtn,airtel,glo,9mobile', 
            'pin' => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $user) {
            
            // Check credit limit and verify PIN
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();
            $creditLimit = CreditLimit::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$creditLimit) {
                return response()->json(['status' => false, 'message' => 'ACTION NOT ALLOWED.'], 400);
            }

            if (!Hash::check($request->pin, $balance->pin)) {
                return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
            }

            if ($creditLimit->limit_amount < $request->amount) {
                return response()->json(['status' => false, 'message' => 'Insufficient Credit Limit.'], 400);
            }

            // Generate unique request ID
            $requestId = 'BORROW_REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            // Deduct credit limit and increase owe amount
            try {
                $creditLimit->limit_amount -= $request->amount;
                $balance->owe += $request->amount;
                $creditLimit->save();
                $balance->save();

                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'BORROW',
                    $request->phone_number,
                    strtoupper($request->network_id) . " airtime borrowed for " . $request->phone_number,
                    $requestId,
                    'AIRTIME_BORROW'
                );

            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ], 500);
            }

            // Store the borrow record
            $borrow = Borrow::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'for' => "AIRTIME",
                'status' => 'PENDING'
            ]);

            // Prepare the external API request
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

            // Make the API request
            try {
                $response = Http::withHeaders($headers)->post($apiUrl, $data);
            } catch (\Exception $e) {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_AIRTIME',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the credit limit and reduce owe
                $creditLimit->limit_amount += $request->amount;
                $balance->owe -= $request->amount;
                $creditLimit->save();
                $balance->save();

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'rejected']);

                Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t connect to our endpoint. Please check your internet connection and try again.'
                ], 500);
            }

            // Handle the API response
            if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_AIRTIME',
                    'message' => 'Borrowed airtime purchase successful',
                    'stack_trace' => json_encode($response->json()),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                $transaction->update([
                    'status' => 'SUCCESS',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'approved']);

                Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'SUCCESS'));

                return response()->json([
                    'status' => true, 
                    'message' => 'Airtime borrowed and purchased successfully',
                    'credit_limit_remaining' => $creditLimit->fresh()->limit_amount,
                    'amount_owed' => $balance->fresh()->owe
                ]);

            } else {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_AIRTIME',
                    'message' => $response->json('message') ?? 'API request failed',
                    'stack_trace' => json_encode($response->json(), JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                $creditLimit->limit_amount += $request->amount;
                $balance->owe -= $request->amount;
                $creditLimit->save();
                $balance->save();

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'rejected']);

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