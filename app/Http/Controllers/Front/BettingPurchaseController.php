<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPurchase;
use App\Models\Betting;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Logged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\BettingPurchaseMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use App\Services\CashbackService;
use App\Services\WebTransactionService;

class BettingPurchaseController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('betting');
    }

    public function buybetting(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string',
            'service_id'  => 'required|string|in:1xBet,BangBet,Bet9ja,BetKing,BetLand,BetLion,BetWay,CloudBet,LiveScoreBet,MerryBet,NaijaBet,NairaBet,SupaBet',
            'amount'      => 'required|integer',
            'pin'         => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $user) {
            
            // -----------------------
            // 1️⃣ Check balance and verify PIN
            // -----------------------
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();

            // Verify PIN
            if (!Hash::check($request->pin, $balance->pin)) {
                return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
            }

            // Check wallet balance
            if ($balance->balance < $request->amount) {
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
                    $request->customer_id . ' | ' . $request->service_id,
                    "Betting Topup for " . $request->customer_id,
                    $requestId
                );

                // Refresh balance
                $balance->refresh();
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ], 500);
            }

            // -----------------------
            // 4️⃣ Create betting purchase record (optional)
            // -----------------------
            // $bettingPurchase = Betting::create([
            //     'user_id'      => $user->id,
            //     'customer_id'  => $request->customer_id,
            //     'service_id'   => $request->service_id,
            //     'amount'       => $request->amount,
            //     'total_amount' => $request->amount,
            //     'status'       => 'PENDING'
            // ]);

            // -----------------------
            // 5️⃣ Ebills API integration
            // -----------------------
            $apiToken = env('EBILLS_API_TOKEN');

            $payload = [
                'request_id'  => $requestId,
                'service_id'  => $request->service_id,
                'customer_id' => $request->customer_id,
                'amount'      => $request->amount,
            ];

            try {
                $response = Http::withToken($apiToken)
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/betting', $payload);

                $responseData = $response->json();
            } catch (\Exception $e) {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BETTING',
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
                    $request->customer_id . ' | ' . $request->service_id,
                    'Refund for failed betting topup',
                    'REFUND_' . $requestId
                );

                // Update original transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $bettingPurchase->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new BettingPurchaseMail(
                    $user,
                    $request->customer_id,
                    $request->service_id,
                    $request->amount,
                    'FAILED'
                ));

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t reach our endpoint service. Please check your internet connection and try again.'
                ], 500);
            }

            // -----------------------
            // 6️⃣ Handle API response
            // -----------------------
            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
                // Log success
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BETTING',
                    'message' => 'Betting topup successful',
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                // Update transaction status
                $transaction->update([
                    'status' => 'SUCCESS',
                    'reference' => $requestId
                ]);

                // $bettingPurchase->update(['status' => 'SUCCESS']);

                // -----------------------
                // 7️⃣ Cashback (set to 0 for betting)
                // -----------------------
                $cashback = 0;
                // Note: Betting typically doesn't offer cashback
                // If you want to enable cashback for betting in the future, uncomment below:
                // if (class_exists(CashbackService::class)) {
                //     $cashback = app(CashbackService::class)->calculate($request->amount);
                //     
                //     if ($cashback > 0) {
                //         $cashbackTransaction = $this->transactionService->createTransaction(
                //             $user,
                //             $cashback,
                //             'CREDIT',
                //             $request->customer_id . ' | ' . $request->service_id,
                //             'Cashback for betting topup',
                //             'CASHBACK_' . $requestId
                //         );
                //
                //         $cashbackTransaction->update(['status' => 'SUCCESS']);
                //     }
                // }

                // Send success email
                Mail::to($user->email)->send(new BettingPurchaseMail(
                    $user,
                    $request->customer_id,
                    $request->service_id,
                    $request->amount,
                    'SUCCESS'
                ));

                return response()->json([
                    'status' => true,
                    'message' => 'Betting Topup successful',
                    'cashback' => $cashback,
                    'balance' => $balance->fresh()->balance
                ]);

            } else {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BETTING',
                    'message' => $responseData['message'] ?? 'API request failed',
                    'stack_trace' => json_encode($responseData, JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'CREDIT',
                    $request->customer_id . ' | ' . $request->service_id,
                    'Refund for failed betting topup',
                    'REFUND_' . $requestId
                );

                // Update transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $bettingPurchase->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new BettingPurchaseMail(
                    $user,
                    $request->customer_id,
                    $request->service_id,
                    $request->amount,
                    'FAILED'
                ));

                return response()->json([
                    'status' => false,
                    'message' => 'Betting Topup failed. Your service provider may be unavailable. Please try again later.'
                ], 500);
            }
        });
    }

}