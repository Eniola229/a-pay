<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\DataPurchase;
use App\Models\Transaction;
use App\Services\CashbackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataController extends Controller
{
    /**
     * Get available data plans for a network
     * 
     * @param string $network
     * @param string $phone
     * @return string
     */
    public function getPlans($network, $phone)
    {
        // Fetch data plans from API
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
        $allPlans = $response->json()['data'] ?? [];
        $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->values();

        if ($networkPlans->isEmpty()) {
            return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
        }

        $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans for {$phone}:\n\n";
        foreach ($networkPlans as $p) {
            $planListMsg .= "- " . $p['data_plan'] . " (₦" . $p['price'] . ")\n";
        }
        $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n📝 Just reply with your choice in this format:\n\n*data 09079916807 1GB*\n\nFor example:\n*data 09079916807 100MB*\n*data 09079916807 3.2GB*\n*data 09079916807 3.2GB - 2 days*";
        
        return $planListMsg;
    }

    /**
     * Process data purchase
     * 
     * @param object $user
     * @param string $network
     * @param string $phone
     * @param string $plan
     * @return string
     */
    public function purchase($user, $network, $phone, $plan)
    {
        // Fetch all plans and find the matching one
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
        $allPlans = $response->json()['data'] ?? [];
        $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->all();

        // Normalize the user's plan input for better matching
        $userPlanNormalized = preg_replace('/\s+/', ' ', strtolower(trim($plan)));
        
        // Add space before min/mins if missing (e.g., "1.5mins" becomes "1.5 mins")
        $userPlanNormalized = preg_replace('/(\d)(min|mins|minutes)/i', '$1 $2', $userPlanNormalized);
        
        // Find matching plan
        $selectedPlan = collect($networkPlans)->first(function ($p) use ($userPlanNormalized) {
            $planData = preg_replace('/\s+/', ' ', strtolower(trim($p['data_plan'])));
            
            // Also add space before min/mins in database plan if missing
            $planData = preg_replace('/(\d)(min|mins|minutes)/i', '$1 $2', $planData);
            
            // Normalize both strings further: make mins/min consistent, days/day consistent
            $userNorm = str_replace(['mins', 'days', 'months', 'weeks'], ['min', 'day', 'month', 'week'], $userPlanNormalized);
            $planNorm = str_replace(['mins', 'days', 'months', 'weeks'], ['min', 'day', 'month', 'week'], $planData);
            
            // Exact match after normalization
            if ($planNorm === $userNorm) {
                return true;
            }
            
            // Starts-with match after normalization
            if (strpos($planNorm, $userNorm) === 0) {
                return true;
            }
            
            return false;
        });

        if (!$selectedPlan) {
            return "⚠️ The plan *{$plan}* is not available for *" . strtoupper($network) . "*.\n\nPlease choose a valid plan and reply:\n*data {$phone} [PLAN]*\n\n Or data " . strtoupper($network) . " to see all available plans";
        }

        $planName = $selectedPlan['data_plan'];
        $planPrice = $selectedPlan['price'];
        $variationId = $selectedPlan['variation_id'];

        // Check user balance
        $balance = Balance::where('user_id', $user->id)->first();
        if (!$balance || $balance->balance < $planPrice) {
            $shortBy = $planPrice - ($balance->balance ?? 0);
            return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$planPrice} - {$planName}\n🔴 Short by: ₦{$shortBy}\n\nPlease fund your wallet and try again! 💳";
        }

        // Deduct balance
        $balance->decrement('balance', $planPrice);

        // Create transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $planPrice,
            'beneficiary' => $phone,
            'description' => "Data purchase: {$planName}",
            'type' => 'DEBIT',
            'status' => 'PENDING'
        ]);

        // Create data purchase record
        $dataPurchase = DataPurchase::create([
            'user_id' => $user->id,
            'phone_number' => $phone,
            'data_plan_id' => $variationId,
            'network_id' => $network,
            'amount' => $planPrice,
            'status' => 'PENDING'
        ]);

        // Call API to process purchase
        $apiToken = env('EBILLS_API_TOKEN');
        $requestId = 'REQ_' . strtoupper(Str::random(12));

        try {
            $response = Http::withToken($apiToken)
                ->timeout(15)
                ->post('https://ebills.africa/wp-json/api/v2/data', [
                    'request_id' => $requestId,
                    'phone' => $phone,
                    'service_id' => $network,
                    'variation_id' => $variationId,
                ]);
            $responseData = $response->json();
        } catch (\Exception $e) {
            // Refund balance on network error
            $balance->increment('balance', $planPrice);
            return "⚠️ Could not reach data provider. Please try again later.";
        }

        // Handle success
        if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
            $transaction->update(['status' => 'SUCCESS', 'reference' => $requestId]);
            $dataPurchase->update(['status' => 'SUCCESS']);

            // Calculate and apply cashback
            $cashback = CashbackService::calculate($planPrice);
            $balance->increment('balance', $cashback);
            $transaction->update(['cash_back' => $cashback]);

            return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$planName}* data has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$planPrice}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your data! 📡🚀";
        } else {
            // Refund balance on failure
            Log::error('Data purchase failed', ['response' => $responseData]);
            $balance->increment('balance', $planPrice);
            $transaction->update(['status' => 'ERROR']);
            $dataPurchase->update(['status' => 'FAILED']);

            return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$planPrice} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
        }
    }

    /**
     * Detect network from phone prefix
     * 
     * @param string $phone
     * @return string|null
     */
    public function detectNetwork($phone)
    {
        $prefix = substr($phone, 0, 4);
        $networkPrefixes = [
            'mtn' => ['0803','0806','0703','0702','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
            'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
            'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
            '9mobile' => ['0809','0817','0818','0909','0908']
        ];

        foreach ($networkPrefixes as $net => $prefixes) {
            if (in_array($prefix, $prefixes)) {
                return $net;
            }
        }

        return null;
    }
}