<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\AirtimePurchase;
use App\Models\Logged;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AirtimeController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Process airtime purchase
     * 
     * @param object $user
     * @param string $network
     * @param float $amount
     * @param string $phone
     * @return string
     */
    public function purchase($user, $network, $amount, $phone)
    {
        return DB::transaction(function () use ($user, $network, $amount, $phone) {

            // -----------------------
            // 1️⃣ Check balance
            // -----------------------
            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $amount) {
                return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$amount}\n\nPlease fund your wallet and try again! 💳";
            }

            // -----------------------
            // 2️⃣ Deduct balance via TransactionService (DEBIT)
            // -----------------------
            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $amount,
                    'DEBIT',
                    $phone,
                    strtoupper($network) . " airtime purchase for " . $phone,
                    $requestId 
                );

                // Refresh Balance for display if needed
                $balance->refresh();
            } catch (\Exception $e) {
                return "😔 Oops! Something seem wrong...";
            }

            // -----------------------
            // 3️⃣ Create Airtime Purchase record
            // -----------------------
            // $airtime = AirtimePurchase::create([
            //     'user_id' => $user->id,
            //     'phone_number' => $phone,
            //     'amount' => $amount,
            //     'network_id' => $network,
            //     'status' => 'PENDING'
            // ]);

            // -----------------------
            // 4️⃣ Prepare API call
            // -----------------------
            $apiUrl = 'https://ebills.africa/wp-json/api/v2/airtime';
            $headers = [
                'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                'Content-Type' => 'application/json'
            ];
            $data = [
                'request_id' => $requestId,
                'phone' => $phone,
                'service_id' => $network,
                'amount' => $amount
            ];

            // -----------------------
            // 5️⃣ Make API call
            // -----------------------
            try {
                $response = Http::withHeaders($headers)->post($apiUrl, $data);
            } catch (\Exception $e) {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund balance on network error (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $amount,
                    'CREDIT',
                    $phone, 
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId 
                );

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $airtime->update(['status' => 'FAILED']);

                return "⚠️ Network error. Please try again later.";
            }

            // -----------------------
            // 6️⃣ Process API response
            // -----------------------
            if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => 'Airtime purchase successful',
                    'stack_trace' => json_encode($response->json()), // Full response
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);
                // Update records to success
                $transaction->update(['status' => 'SUCCESS', 'reference' => $requestId]);
                // $airtime->update(['status' => 'SUCCESS']);

                // -----------------------
                // 7️⃣ Calculate and apply cashback
                // -----------------------
                $cashback = 0;
                if (class_exists(\App\Services\CashbackService::class)) {
                    $cashback = app(\App\Services\CashbackService::class)->calculate($amount);

                    if ($cashback > 0) {
                        $cashbackTransaction = $this->transactionService->createTransaction(
                            $user,
                            $cashback,
                            'CREDIT',
                            $phone, 
                            'Cashback for airtime purchase',
                            'CASHBACK_' . $requestId 
                        );

                        $cashbackTransaction->update([
                            'status' => 'SUCCESS'
                        ]);
                    }
                }

                return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$amount}* airtime has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$amount}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your airtime! 📡🚀";

            } else {
                // Refund balance on failure (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $amount,
                    'CREDIT',
                    $phone, 
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId,
                );

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => $response->json('message') ?? 'API request failed',
                    'stack_trace' => json_encode($response->json(), JSON_PRETTY_PRINT), // Pretty format
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // $airtime->update(['status' => 'FAILED']);

                return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$amount} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
            }
        });
    }
}
