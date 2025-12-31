<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPurchase;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Logged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\DataPurchaseMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use App\Services\CashbackService;
use App\Services\WebTransactionService;

class DataPurchaseController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('data');
    }

    public function getDataPlans($networkId)
    {
        // Normalize network ID
        $networkId = strtolower($networkId);

        // Fetch all data from the external API
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');

        if ($response->failed()) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data from provider.'
            ], 500);
        }

        // Access the 'data' key from the response
        $allData = $response->json()['data'] ?? [];

        // Filter only plans for the requested network
        $filteredPlans = collect($allData)->filter(function ($item) use ($networkId) {
            $serviceId = strtolower($item['service_id']);
            return $serviceId === $networkId;
        });

        // Reformat data to match the expected structure
        $formatted = [];
        foreach ($filteredPlans as $plan) {
            $planCode = $plan['variation_id'] ?? uniqid();
            $formatted[$planCode] = [
                'name'  => $plan['data_plan'] ?? 'Unnamed Plan',
                'price' => $plan['price'] ?? 0,
            ];
        }

        if (empty($formatted)) {
            return response()->json([
                'status' => false,
                'message' => 'No plans found for this network.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $formatted
        ]);
    }

    public function buyData(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'network_id'   => 'required|string|in:mtn,glo,airtel,9mobile,smile',
            'variation_id' => 'required|string',
            'pin'          => 'required|string|min:4|max:4'
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

            // -----------------------
            // 2️⃣ Fetch data plan details
            // -----------------------
            $pricingDataResponse = $this->getDataPlans($request->network_id);
            $pricingData = json_decode($pricingDataResponse->getContent(), true);

            if (!$pricingData['status']) {
                return response()->json(['status' => false, 'message' => $pricingData['message']], 500);
            }

            $plans = $pricingData['data'];
            $variationId = (int) $request->variation_id;

            $planDetails = $plans[$variationId] ?? null;

            if (!$planDetails) {
                return response()->json(['status' => false, 'message' => 'Invalid data plan selected.'], 400);
            }

            $planPrice = $planDetails['price'];
            $planName = $planDetails['name'];

            // Check wallet balance
            if ($balance->balance < $planPrice) {
                return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
            }

            // -----------------------
            // 3️⃣ Generate unique request ID
            // -----------------------
            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            // -----------------------
            // 4️⃣ Deduct balance via WebTransactionService (DEBIT)
            // -----------------------
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'DEBIT',
                    $request->phone_number,
                    "Data purchase: " . $planName,
                    $requestId,
                    'DATA' // Service type for cashback calculation
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
            // 5️⃣ Create data purchase record (optional)
            // -----------------------
            // $dataPurchase = DataPurchase::create([
            //     'user_id'      => $user->id,
            //     'phone_number' => $request->phone_number,
            //     'data_plan_id' => $request->variation_id,
            //     'network_id'   => $request->network_id,
            //     'amount'       => $planPrice,
            //     'status'       => 'PENDING'
            // ]);

            // -----------------------
            // 6️⃣ Ebills API integration
            // -----------------------
            $apiToken = env('EBILLS_API_TOKEN');

            $payload = [
                'request_id'   => $requestId,
                'phone'        => $request->phone_number,
                'service_id'   => $request->network_id,
                'variation_id' => $request->variation_id,
            ];

            try {
                $response = Http::withToken($apiToken)
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/data', $payload);

                $responseData = $response->json();
            } catch (\Exception $e) {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $request->phone_number,
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId
                );

                // Update original transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $dataPurchase->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t reach our endpoint service. Please check your internet connection and try again.'
                ], 500);
            }

            // -----------------------
            // 7️⃣ Handle API response
            // -----------------------
            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
                // Log success
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => 'Data purchase successful',
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

                // $dataPurchase->update(['status' => 'SUCCESS']);

                // -----------------------
                // 8️⃣ Calculate and apply cashback
                // -----------------------
                $cashback = 0;
                if (class_exists(CashbackService::class)) {
                    $cashback = app(CashbackService::class)->calculate($planPrice);

                    if ($cashback > 0) {
                        $cashbackTransaction = $this->transactionService->createTransaction(
                            $user,
                            $cashback,
                            'CREDIT',
                            $request->phone_number,
                            'Cashback for data purchase',
                            'CASHBACK_' . $requestId
                        );

                        $cashbackTransaction->update([
                            'status' => 'SUCCESS'
                        ]);
                    }
                }

                // Send success email
                Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'SUCCESS'));

                return response()->json([
                    'status' => true,
                    'message' => 'Data purchased successfully',
                    'cashback' => $cashback,
                    'balance' => $balance->fresh()->balance
                ]);

            } else {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'DATA',
                    'message' => $responseData['message'] ?? 'API request failed',
                    'stack_trace' => json_encode($responseData, JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'CREDIT',
                    $request->phone_number,
                    'Refund for failed data purchase',
                    'REFUND_' . $requestId
                );

                // Update transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $dataPurchase->update(['status' => 'FAILED']);

                // Send failure email
                Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'Data purchase failed. Your service provider may be unavailable. Please try again later.'
                ], 500);
            }
        });
    }

    public function recentPurchases()
    {
        $purchases = DataPurchase::where('user_id', Auth::id())
            ->select('phone_number')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($purchases);
    }
}