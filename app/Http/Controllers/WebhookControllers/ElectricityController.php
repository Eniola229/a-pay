<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\ElectricityPurchase;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ElectricityPaymentReceipt;

class ElectricityController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Process electricity payment
     */
    public function purchase($user, $meterNumber, $amount, $provider)
    {
        $providerMap = [
            'abuja' => 'abuja-electric',
            'eko' => 'eko-electric',
            'ibadan' => 'ibadan-electric',
            'ikeja' => 'ikeja-electric',
            'jos' => 'jos-electric',
            'kaduna' => 'kaduna-electric',
            'kano' => 'kano-electric',
            'portharcourt' => 'portharcourt-electric'
        ];

        if ($amount < 500) {
            return "⚠️ Minimum amount is ₦500.\n\nYou entered: ₦" . number_format($amount) . "\n\nPlease try again with a higher amount.";
        }

        $balance = Balance::where('user_id', $user->id)->first();
        if (!$balance) {
            return "❌ Account error. Please contact support.";
        }

        $serviceFee = 39;
        $systemFee = 60;
        $totalAmount = $amount + $serviceFee + $systemFee;

        if ($balance->balance < $totalAmount) {
            $shortBy = $totalAmount - $balance->balance;
            return "😔 Insufficient balance.\n\n💰 Your wallet: ₦" . number_format($balance->balance) . "\n💸 Total needed: ₦" . number_format($totalAmount) . " (₦" . number_format($amount) . " + fees)\n🔴 Short by: ₦" . number_format($shortBy) . "\n\nPlease fund your wallet! 💳";
        }

        // Generate unique request ID for transaction reference
        $requestId = 'REQ_' . strtoupper(Str::random(12));

        // Deduct balance and create DEBIT transaction
        try {
            $transaction = $this->transactionService->createTransaction(
                $user,
                $totalAmount,
                'DEBIT',
                $meterNumber, 
                "Electricity bill payment for meter " . $meterNumber,
                $requestId // ✅ reference
            );
            $balance->refresh();
        } catch (\Exception $e) {
            return "😔 Oops! Something went wrong...";
        }

        // Create electricity purchase record
        $electricityPurchase = ElectricityPurchase::create([
            'user_id'      => $user->id,
            'meter_number' => $meterNumber,
            'provider_id'  => $providerMap[$provider] ?? $provider,
            'amount'       => $amount,
            'service_fee'  => $serviceFee,
            'total_amount' => $totalAmount,
            'status'       => 'PENDING'
        ]);

        // Call API
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
            // Refund via TransactionService with ERROR status and reference
            $this->transactionService->refundTransaction(
                $transaction,
                $balance,
                $requestId,
                $meterNumber 
            );
            // $electricityPurchase->update(['status' => 'FAILED']);
            return "⚠️ Could not reach provider. Please try again later. Your balance has been restored.";
        }

        // Handle success
        if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
            $token = $responseData['token'] ?? 'N/A';
            $units = $responseData['units'] ?? 'N/A';

            $this->transactionService->markTransactionSuccess(
                $transaction,
                "Electricity bill payment for meter {$meterNumber} | Token: {$token} | Units: {$units}",
                $requestId,
                $meterNumber 
            );
            // $electricityPurchase->update(['status' => 'SUCCESS']);

            // Send email
            try {
                Mail::to($user->email)->send(new ElectricityPaymentReceipt([
                    'user' => $user,
                    'meterNumber' => $meterNumber,
                    'provider' => $provider,
                    'amount' => $amount,
                    'token' => $token,
                    'units' => $units,
                    'status' => 'SUCCESS'
                ]));
            } catch (\Exception $e) {
                Log::error('Email send failed', ['error' => $e->getMessage()]);
            }

            return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Electricity bill paid successfully!\n\n📊 Details:\n💡 Meter: *{$meterNumber}*\n🏢 Provider: *" . ucfirst($provider) . "*\n💰 Amount Paid: ₦" . number_format($amount) . "\n⚡ Token: *{$token}*\n📈 Units: *{$units}*\n\n🎁 Check your email for receipt!\n\nEnjoy your power supply! 🔌";
        } else {
            // Refund via TransactionService with ERROR status and reference
            $this->transactionService->refundTransaction(
                $transaction,
                $balance,
                $requestId,
                $meterNumber 
            );
            // $electricityPurchase->update(['status' => 'FAILED']);

            $errorMsg = $responseData['message'] ?? 'Payment failed. Please try again.';
            return "❌ Payment failed.\n\n⚠️ " . $errorMsg . "\n\nYour balance of ₦" . number_format($totalAmount) . " has been restored.\n\nPlease try again or contact support. 📞";
        }
    }
}
