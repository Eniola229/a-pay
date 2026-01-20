<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\DataPurchase;
use App\Models\Logged;
use App\Services\TransactionService;
use App\Services\CashbackService;
use App\Services\ReceiptGenerator; // Added
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Added for Cache
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    protected TransactionService $transactionService;
    protected ReceiptGenerator $receiptGenerator; // Added

    public function __construct(TransactionService $transactionService, ReceiptGenerator $receiptGenerator) // Updated
    {
        $this->transactionService = $transactionService;
        $this->receiptGenerator = $receiptGenerator; // Added
    }

    public function detectNetwork($phone)
    {
        $prefix = substr($phone, 0, 4);

        $networks = [
            'mtn' => ['0803','0806','0703','0702','0706','0704','0810','0813','0814','0816','0903','0906','0913','0916','0707'],
            'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
            'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912','0904'],
            '9mobile' => ['0809','0817','0818','0909','0908']
        ];

        foreach ($networks as $network => $prefixes) {
            if (in_array($prefix, $prefixes)) {
                return $network;
            }
        }

        return null; 
    }

    public function getPlans($network, $phone)
    {
        // Fetch plans directly without caching
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
        $allPlans = $response->json()['data'] ?? [];
        
        $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->values();
        
        if ($networkPlans->isEmpty()) {
            return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
        }
        
        $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans for *{$phone}*:\n\n";
        $displayPlans = $networkPlans->take(50);
        
        foreach ($displayPlans as $index => $p) {
            $planListMsg .= ($index + 1) . ". " . $p['data_plan'] . " - ₦" . number_format($p['price']) . "\n";
        }
        
        if ($networkPlans->count() > 50) {
            $planListMsg .= "\n💡 Showing first 50 plans.\n";
        }
        
        $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n";
        $planListMsg .= "📝 Reply with this format:\n\n";
        $planListMsg .= "*data 2 09079916807* \n";
        $planListMsg .= "or \n";
        $planListMsg .= "*data 09079916807 2*\n\n";
        $planListMsg .= "⏱️ *Please respond within 1 minute.*";
        
        return $planListMsg;
    }

    public function purchase($user, $network, $phone, $plan)
    {
        return DB::transaction(function () use ($user, $network, $phone, $plan) {

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

            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance || $balance->balance < $planPrice) {
                $shortBy = $planPrice - ($balance->balance ?? 0);
                return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$planPrice} - {$planName}\n🔴 Short by: ₦{$shortBy}\n\nPlease fund your wallet and try again! 💳";
            }

            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'DEBIT',
                    $phone, 
                    "Data purchase: {$planName}",
                    $requestId,
                    'DATA' // Service type for cashback calculation
                );
                $balance->refresh();
            } catch (\Exception $e) {
                return "😔 Oops! Something seems wrong";
            }

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
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);
                
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $user->mobile, 
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId 
                );

                $transaction->update(['status' => 'ERROR', 'reference' => $requestId]);

                return "⚠️ Could not reach data provider. Please try again later.";
            }

            if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => 'Data purchase successful - Code: ' . ($responseData['code'] ?? 'N/A'),
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                $transaction->update(['status' => 'SUCCESS', 'reference' => $requestId]);

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
                            'CASHBACK_' . $requestId 
                        );
                        $cashbackTransaction->update(['status' => 'SUCCESS']);
                    }
                }

                // === GENERATE DATA RECEIPT ===
                try {
                    $receiptUrl = $this->receiptGenerator->generateDataReceipt([
                        'amount' => $planPrice,
                        'phone' => $phone,
                        'network' => $network,
                        'plan' => $planName, 
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
                            'message' => "✅ Your {$planName} data has been activated!"
                        ],
                        [
                            'type' => 'text',
                            'message' => "🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\nYour new wallet balance is ₦" . number_format($balance->balance, 2) . ".\nThank you for using A-Pay 💚"
                        ]
                    ];

                } catch (\Exception $e) {
                    \Log::error('Receipt generation failed: ' . $e->getMessage());
                    
                    return [
                        'type' => 'text',
                        'message' => "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$planName}* data has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$planPrice}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your data! 📡🚀"
                    ];
                }

            } else {
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $user->mobile,
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId 
                );

                $transaction->update(['status' => 'ERROR', 'reference' => $requestId]);

                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => json_encode($responseData),
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);
                Log::error('Data purchase failed', ['response' => $responseData]);

                // Check if a specific message exists in the API response with invalid_service or duplicate_order code
                if (isset($responseData['code']) && in_array($responseData['code'], ['invalid_service', 'duplicate_order']) && isset($responseData['message'])) {
                    // Show the API error message AND inform about the refund
                    return "❌ " . $responseData['message'] . "\n\nYour balance of ₦{$planPrice} has been refunded.";
                }
                // Otherwise, return the default generic message
                return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$planPrice} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
                

            }
        });
    }
}