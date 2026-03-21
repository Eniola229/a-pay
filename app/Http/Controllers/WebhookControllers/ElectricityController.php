<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\ElectricityPurchase;
use App\Models\Logged;
use App\Services\TransactionService;
use App\Services\ReceiptGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ElectricityPaymentReceipt;
 
class ElectricityController extends Controller
{
    protected $transactionService;
    protected $receiptGenerator;

    public function __construct(TransactionService $transactionService, ReceiptGenerator $receiptGenerator)
    {
        $this->transactionService = $transactionService;
        $this->receiptGenerator = $receiptGenerator;
    }

    private function sendElectricityConfirmation($user, $meterNumber, $amount, $provider)
    {
        $fee   = 99;
        $total = $amount + $fee;

        $session = WhatsappSession::firstOrNew([
            'user_id' => $user->id,
            'context' => 'pending_confirm'
        ]);
        $session->id = $session->id ?? Str::uuid();
        $session->data = json_encode([
            'service'      => 'electricity',
            'meter_number' => $meterNumber,
            'amount'       => $amount,
            'provider'     => $provider,
        ]);
        $session->save();

        $body = "⚡ *Electricity Payment*\n\n" .
                "🔢 Meter: *{$meterNumber}*\n" .
                "🏢 Provider: *" . ucfirst($provider) . "*\n" .
                "💰 Amount: *₦" . number_format($amount) . "*\n" .
                "💳 Fee: *₦" . number_format($fee) . "*\n" .
                "💵 Total: *₦" . number_format($total) . "*";

        $this->sendInteractiveButtons(
            $user->mobile,
            '🛒 Confirm Order',
            $body,
            'Tap Confirm to proceed or Cancel to abort',
            [
                ['id' => 'confirm_yes', 'title' => '✅ Confirm'],
                ['id' => 'confirm_no',  'title' => '❌ Cancel'],
            ]
        );
    }
    public function purchase($user, $meterNumber, $amount, $provider)
    {
        return DB::transaction(function () use ($user, $meterNumber, $amount, $provider) {

            $providerMap = [
                'abuja' => 'abuja-electric',
                'eko' => 'eko-electric',
                'ibadan' => 'ibadan-electric',
                'ikeja' => 'ikeja-electric',
                'jos' => 'jos-electric',
                'kaduna' => 'kaduna-electric',
                'kano' => 'kano-electric',
                'enugu' => 'enugu-electric',
                'benin' => 'benin-electric',
                'aba' => 'aba-electric',
                'yola' => 'yola-electric',
                'portharcourt' => 'portharcourt-electric'
            ];

            if ($amount < 1000) {
                return "⚠️ Minimum amount is ₦1000.\n\nYou entered: ₦" . number_format($amount) . "\n\nPlease try again with a higher amount.";
            }

            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance) {
                return "❌ Account error. Please contact support.";
            }

            $transactionFee = 99;
            $totalAmount = $amount + $transactionFee;

            if ($balance->balance < $totalAmount) {
                $shortBy = $totalAmount - $balance->balance;
                return "😔 Insufficient balance.\n\n💰 Your wallet: ₦" . number_format($balance->balance) . "\n💸 Total needed: ₦" . number_format($totalAmount) . " (₦" . number_format($amount) . " + fees)\n🔴 Short by: ₦" . number_format($shortBy) . "\n\nPlease fund your wallet! 💳";
            }

            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            try {
                // Debit only the electricity amount (not including fee)
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $amount,
                    'DEBIT',
                    $meterNumber, 
                    "Electricity bill payment for meter " . $meterNumber,
                    $requestId,
                    null,
                    $transactionFee
                );
                
                // Debit the transaction fee separately
                $feeTransaction = $this->transactionService->createTransaction(
                    $user,
                    $transactionFee,
                    'DEBIT',
                    'SYSTEM',
                    "Transaction fee for electricity purchase - Meter: " . $meterNumber,
                    'FEE_' . $requestId,
                    null,
                    0
                );
                
                $balance->refresh();
            } catch (\Exception $e) {
                return "Processing...\n\n if i dont reply within 1 min please message support";
            }

            $apiToken = env('EBILLS_API_TOKEN');

            try {
                $response = Http::withToken($apiToken)
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/electricity', [
                        'request_id'   => $requestId,
                        'customer_id'  => $meterNumber,
                        'service_id'   => $providerMap[$provider] ?? $provider,
                        'variation_id' => 'prepaid',
                        'amount'       => $amount,
                    ]);
                $responseData = $response->json();
            } catch (\Exception $e) {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'ELECTRICITY',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);
                
                // Refund both transactions
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $user->mobile,
                    "Refund for electricity purchase failed - Provider unreachable for meter {$meterNumber}",
                    "REFUND_" . $requestId
                );
                
                $this->transactionService->refundTransaction(
                    $feeTransaction,
                    $balance,
                    'FEE_' . $requestId,
                    'SYSTEM',
                    "Refund of transaction fee - Provider unreachable for meter {$meterNumber}",
                    "REFUND_FEE_" . $requestId
                );
                
                return "⚠️ Could not reach provider. Please try again later. Your balance has been restored.";
            }

            if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'ELECTRICITY',
                    'message' => 'Electricity purchase successful',
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);
                
                // Get token and units from nested 'data' object
                $token = $responseData['data']['token'] ?? 'N/A';
                $units = $responseData['data']['units'] ?? 'Not Provided';

                // Mark both transactions as SUCCESS
                $this->transactionService->markTransactionSuccess(
                    $transaction,
                    "Electricity bill payment for meter {$meterNumber} | Token: {$token} | Units: {$units}",
                    $requestId,
                    $meterNumber 
                );
                
                $this->transactionService->markTransactionSuccess(
                    $feeTransaction,
                    "Transaction fee for electricity purchase - Meter: {$meterNumber} | Token: {$token}",
                    'FEE_' . $requestId,
                    'SYSTEM'
                );

                try {
                    Mail::to($user->email)->send(new ElectricityPaymentReceipt([
                        'user' => $user,
                        'meterNumber' => $meterNumber,
                        'provider' => $provider,
                        'amount' => $totalAmount,
                        'token' => $token,
                        'units' => $units,
                        'customer_address' => $responseData['data']['customer_address'] ?? 'N/A',
                        'customer_name_m' => $responseData['data']['customer_name'] ?? 'N/A',
                        'status' => 'SUCCESS'
                    ]));
                } catch (\Exception $e) {
                    Log::error('Email send failed', ['error' => $e->getMessage()]);
                }

                // === GENERATE ELECTRICITY RECEIPT ===
                try {
                    $receiptUrl = $this->receiptGenerator->generateElectricityReceipt([
                        'amount' => $totalAmount, 
                        'meter_number' => $meterNumber,
                        'provider' => $provider,
                        'token' => $token,
                        'units' => $units,
                        'reference' => $requestId,
                        'customer_name' => $user->name,
                        'customer_address' => $responseData['data']['customer_address'] ?? 'N/A',
                        'customer_name_m' => $responseData['data']['customer_name'] ?? 'N/A',
                        'account_number' => $user->account_number,
                        'date' => now()->format('d M Y, h:i A')
                    ]);

                    return [
                        [
                            'type' => 'image',
                            'receipt_url' => $receiptUrl,
                            'message' => "✅ Electricity bill paid successfully!"
                        ],
                        [
                            'type' => 'text',
                            'message' => "Your new wallet balance is ₦" . number_format($balance->balance, 2) . ".\nThank you for using A-Pay 💚"
                        ]
                    ];

                } catch (\Exception $e) {
                    Log::error('Receipt generation failed: ' . $e->getMessage());
                    
                    return [
                        'type' => 'text',
                        'message' => "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Electricity bill paid successfully!\n\n📊 Details:\n💡 Meter: *{$meterNumber}*\n🏢 Provider: *" . ucfirst($provider) . "*\n💰 Amount Paid: ₦" . number_format($totalAmount) . "\n⚡ Token: *{$token}*\n📈 Units: *{$units}*\n\n🎁 Check your email for receipt!\n\nEnjoy your power supply! 🔌"
                    ];
                }

            } else {
                // Refund both transactions
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $user->mobile,
                    "Refund for electricity purchase - Payment unsuccessful for meter {$meterNumber}",
                    "REFUND_" . $requestId
                );
                
                $this->transactionService->refundTransaction(
                    $feeTransaction,
                    $balance,
                    'FEE_' . $requestId,
                    'SYSTEM',
                    "Refund of transaction fee - Payment unsuccessful for meter {$meterNumber}",
                    "REFUND_FEE_" . $requestId
                );

                $errorMsg = $responseData['message'] ?? 'Payment failed. Please try again.';
                Logged::create([
                        'user_id' => $user->id,
                        'for' => 'ELECTRICITY',
                        'message' => json_encode($responseData),
                        'stack_trace' => json_encode($responseData),
                        't_reference' => $requestId,
                        'from' => 'EBILLS',
                        'type' => 'FAILED',
                ]);
                return "❌ Payment failed.\n\nYour balance of ₦" . number_format($totalAmount) . " has been restored.\n\nPlease try again or contact support. 📞";
            }
        });
    }
}