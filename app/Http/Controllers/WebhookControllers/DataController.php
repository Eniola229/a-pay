<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\DataPurchase;
use App\Services\TransactionService;
use App\Services\CashbackService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function detectNetwork($phone)
    {
        $prefix = substr($phone, 0, 4);

        $networks = [
            'mtn' => ['0803','0806','0703','0702','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
            'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
            'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
            '9mobile' => ['0809','0817','0818','0909','0908']
        ];

        foreach ($networks as $network => $prefixes) {
            if (in_array($prefix, $prefixes)) {
                return $network;
            }
        }

        return null; // unknown network
    }
    /**
     * Get available data plans for a network
     */
    public function getPlans($network, $phone)
    {
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
        $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n📝 Just reply with your choice in this format:\n\n*data 09079916807 1GB*";

        return $planListMsg;
    }

    /**
     * Process data purchase
     */
    public function purchase($user, $network, $phone, $plan)
    {
        return DB::transaction(function () use ($user, $network, $phone, $plan) {

            // -----------------------
            // 1️⃣ Fetch plan info
            // -----------------------
            $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
            $allPlans = $response->json()['data'] ?? [];
            $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->all();

            $userPlanNormalized = preg_replace('/\s+/', ' ', strtolower(trim($plan)));
            $userPlanNormalized = preg_replace('/(\d)(min|mins|minutes)/i', '$1 $2', $userPlanNormalized);

            $selectedPlan = collect($networkPlans)->first(function ($p) use ($userPlanNormalized) {
                $planData = preg_replace('/\s+/', ' ', strtolower(trim($p['data_plan'])));
                $planData = preg_replace('/(\d)(min|mins|minutes)/i', '$1 $2', $planData);
                $userNorm = str_replace(['mins','days','months','weeks'], ['min','day','month','week'], $userPlanNormalized);
                $planNorm = str_replace(['mins','days','months','weeks'], ['min','day','month','week'], $planData);

                return $planNorm === $userNorm || strpos($planNorm, $userNorm) === 0;
            });

            if (!$selectedPlan) {
                return "⚠️ The plan *{$plan}* is not available for *" . strtoupper($network) . "*.\n\nPlease choose a valid plan and reply:\n*data {$phone} [PLAN]*\n\n Or data " . strtoupper($network) . " to see all available plans";
            }

            $planName = $selectedPlan['data_plan'];
            $planPrice = $selectedPlan['price'];
            $variationId = $selectedPlan['variation_id'];

            // -----------------------
            // 2️⃣ Check balance
            // -----------------------
            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $planPrice) {
                $shortBy = $planPrice - ($balance->balance ?? 0);
                return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$planPrice} - {$planName}\n🔴 Short by: ₦{$shortBy}\n\nPlease fund your wallet and try again! 💳";
            }

            // -----------------------
            // 3️⃣ Deduct balance via TransactionService (DEBIT)
            // -----------------------
            $requestId = 'REQ_' . strtoupper(Str::random(12));
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'DEBIT',
                    $phone, 
                    "Data purchase: {$planName}",
                    $requestId // ✅ reference
                );

                $balance->refresh();
            } catch (\Exception $e) {
                return "😔 Oops! Something seems wrong";
            }

            // -----------------------
            // 4️⃣ Create DataPurchase record
            // -----------------------
            $dataPurchase = DataPurchase::create([
                'user_id' => $user->id,
                'phone_number' => $phone,
                'data_plan_id' => $variationId,
                'network_id' => $network,
                'amount' => $planPrice,
                'status' => 'PENDING'
            ]);

            // -----------------------
            // 5️⃣ Call API
            // -----------------------
            $apiToken = env('EBILLS_API_TOKEN');

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
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $phone, 
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId // ✅ reference
                );

                $transaction->update(['status' => 'ERROR', 'reference' => $requestId]);
                $dataPurchase->update(['status' => 'FAILED']);

                return "⚠️ Could not reach data provider. Please try again later.";
            }

            // -----------------------
            // 6️⃣ Handle API response
            // -----------------------
            if ($response->successful() && ($responseData['code'] ?? '') === 'success') {

                $transaction->update(['status' => 'SUCCESS', 'reference' => $requestId]);
                $dataPurchase->update(['status' => 'SUCCESS']);

                // Apply cashback
                $cashback = 0;
                if (class_exists(CashbackService::class)) {
                    $cashback = app(CashbackService::class)->calculate($planPrice);
                    if ($cashback > 0) {
                        $cashbackTransaction = $this->transactionService->createTransaction(
                            $user,
                            $cashback,
                            'CREDIT',
                            $phone, 
                            'Cashback for data purchase',
                            'CASHBACK_' . $requestId // ✅ reference
                        );
                        $cashbackTransaction->update(['status' => 'SUCCESS']);
                    }
                }

                return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$planName}* data has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$planPrice}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your data! 📡🚀";

            } else {
                // Refund balance on failure
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $phone, 
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId // ✅ reference
                );

                $transaction->update(['status' => 'ERROR', 'reference' => $requestId]);
                $dataPurchase->update(['status' => 'FAILED']);

                Log::error('Data purchase failed', ['response' => $responseData]);

                return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$planPrice} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
            }
        });
    }
}
