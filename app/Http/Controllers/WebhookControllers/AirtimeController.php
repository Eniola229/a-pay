<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\AirtimePurchase;
use App\Models\Logged;
use App\Services\TransactionService;
use App\Services\ReceiptGenerator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AirtimeController extends Controller
{
    protected TransactionService $transactionService;
    protected ReceiptGenerator $receiptGenerator;

    public function __construct(TransactionService $transactionService, ReceiptGenerator $receiptGenerator)
    {
        $this->transactionService = $transactionService;
        $this->receiptGenerator = $receiptGenerator;
    }

    /**
     * Process airtime purchase
     * 
     * @param object $user
     * @param string $network
     * @param float $amount
     * @param string $phone
     * @return array Returns message and optional receipt URL
     */
    public function purchase($user, $network, $amount, $phone)
    {
        return DB::transaction(function () use ($user, $network, $amount, $phone) {

            // -----------------------
            // 1️⃣ Check balance
            // -----------------------
            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $amount) {
                return [
                    'type' => 'text',
                    'message' => "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$amount}\n\nPlease fund your wallet and try again! 💳"
                ];
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
                    $requestId,
                    'AIRTIME' // Service type for cashback calculation
                );

                $balance->refresh();
            } catch (\Exception $e) {
                return [
                    'type' => 'text',
                    'message' => "😔 Oops! Something seems wrong..."
                ];
            }

            // -----------------------
            // 3️⃣ Prepare API call
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
            // 4️⃣ Make API call
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
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $user->mobile,
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId
                );

                // $transaction->update([
                //     'status' => 'ERROR',
                //     'reference' => $requestId
                // ]);

                return [
                    'type' => 'text',
                    'message' => "⚠️ Network error. Please try again later."
                ];
            }

            // -----------------------
            // 5️⃣ Process API response
            // -----------------------
            if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => 'Airtime purchase successful',
                    'stack_trace' => json_encode($response->json()),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                $transaction->update(['status' => 'SUCCESS', 'reference' => $requestId]);

                // -----------------------
                // 6️⃣ Calculate and apply cashback
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

                // -----------------------
                // 7️⃣ Generate Image Receipt
                // -----------------------
                try {
                    $receiptUrl = $this->receiptGenerator->generateAirtimeReceipt([
                        'amount' => $amount,
                        'phone' => $phone,
                        'network' => $network,
                        'reference' => $requestId,
                        'cashback' => $cashback,
                        'customer_name' => $user->name,
                        'account_number' => $user->account_number,
                        'date' => now()->format('d M Y, h:i A')
                    ]);

                    return [
                        [
                            'type' => 'image',
                            'receipt_url' => $receiptUrl,
                            'message' => "✅ Your ₦{$amount} airtime has been activated!"
                        ],
                        [
                            'type' => 'text',
                            'message' => "🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\nYour new wallet balance is ₦" . number_format($balance->balance, 2) . ".\nThank you for using A-Pay 💚"
                        ]
                    ];

                } catch (\Exception $e) {
                    // Fallback to text receipt if image generation fails
                    \Log::error('Receipt generation failed: ' . $e->getMessage());
                    
                    return [
                        'type' => 'text',
                        'message' => "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your ₦{$amount} airtime has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$amount}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your airtime! 📡🚀"
                    ];
                }

            } else {
                // Refund balance on failure (CREDIT)
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $user->mobile,
                    'Refund for failed airtime purchase',
                    'REFUND_' . $requestId
                );

                // $transaction->update([
                //     'status' => 'ERROR',
                //     'reference' => $requestId
                // ]);

                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'AIRTIME',
                    'message' => $response->json('message') ?? 'API request failed',
                    'stack_trace' => json_encode($response->json(), JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                $responseData = $response->json();;
                // Check for specific API error message
                if (isset($responseData['code']) && in_array($responseData['code'], ['invalid_service', 'duplicate_order']) && isset($responseData['message'])) {
                    $message = "❌ " . $responseData['message'] . "\n\nYour balance of ₦{$amount} has been refunded.";
                } else {
                    $message = "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$amount} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
                }

                return [
                    'type' => 'text',
                    'message' => $message
                ];
            }
        });
    }
}