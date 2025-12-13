<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\AirtimePurchase;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use App\Services\ReceiptGeneratorService;

class AirtimeController extends Controller
{
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
        // Check balance
        $balance = Balance::where('user_id', $user->id)->first();
        
        if (!$balance || $balance->balance < $amount) {
            return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$amount}\n\nPlease fund your wallet and try again! 💳";
        }

        // Deduct balance
        $balance->balance -= $amount;
        $balance->save();

        // Create airtime purchase record
        $airtime = AirtimePurchase::create([
            'user_id' => $user->id,
            'phone_number' => $phone,
            'amount' => $amount,
            'network_id' => $network,
            'status' => 'PENDING'
        ]);

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'beneficiary' => $phone,
            'description' => strtoupper($network) . " airtime purchase for " . $phone,
            'type' => 'DEBIT',
            'status' => 'PENDING'
        ]);

        // Prepare API call
        $apiUrl = 'https://ebills.africa/wp-json/api/v2/airtime';
        $headers = [
            'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
            'Content-Type' => 'application/json'
        ];
        $data = [
            'request_id' => 'req_' . uniqid(),
            'phone' => $phone,
            'service_id' => $network,
            'amount' => $amount
        ];

        // Make API call
        try {
            $response = Http::withHeaders($headers)->post($apiUrl, $data);
        } catch (\Exception $e) {
            // Refund balance on network error
            $balance->balance += $amount;
            $balance->save();
            return "⚠️ Network error. Please try again later.";
        }

        // Process response
        if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
            // Update records to success
            $transaction->update(['status' => 'SUCCESS']);
            $airtime->update(['status' => 'SUCCESS']);

            // Calculate and apply cashback
            $cashback = 0;
            if (class_exists(\App\Services\CashbackService::class)) {
                $cashback = app(\App\Services\CashbackService::class)->calculate($amount);
                $balance->balance += $cashback;
                $balance->save();
                $transaction->cash_back = $cashback;
                $transaction->save();
            }

            return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$amount}* airtime has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$amount}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your airtime! 📡🚀";
        } else {
            // Refund balance on failure
            $balance->balance += $amount;
            $balance->save();
            
            // Update records to failed
            $transaction->update(['status' => 'ERROR']);
            $airtime->update(['status' => 'FAILED']);
            
            return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$amount} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
        }
    }
}