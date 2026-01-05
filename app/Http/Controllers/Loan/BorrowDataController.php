<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPurchase;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Logged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\DataPurchaseMail;
use Illuminate\Support\Str;
use App\Models\CreditLimit;
use App\Models\Borrow;
use App\Services\WebTransactionService;

class BorrowDataController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('borrow_data');
    }

    public function getDataPlans($networkId)
    {
        $networkId = strtolower($networkId);

        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');

        if ($response->failed()) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data from provider.'
            ], 500);
        }

        $allData = $response->json()['data'] ?? [];

        $filteredPlans = collect($allData)->filter(function ($item) use ($networkId) {
            $serviceId = strtolower($item['service_id']);
            return $serviceId === $networkId;
        });

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
            'network_id'   => 'required|string|in:mtn,glo,airtel,etisalat,9mobile',
            'variation_id' => 'required|string',
            'pin'          => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($request, $user) {
            
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();
            $creditLimit = CreditLimit::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$creditLimit) {
                return response()->json(['status' => false, 'message' => 'ACTION NOT ALLOWED.'], 400);
            }

            if (!Hash::check($request->pin, $balance->pin)) {
                return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
            }

            // Fetch data plan details
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

            if ($creditLimit->limit_amount < $planPrice) {
                return response()->json(['status' => false, 'message' => 'Insufficient Credit Limit.'], 400);
            }

            $requestId = 'BORROW_DATA_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            try {
                $creditLimit->limit_amount -= $planPrice;
                $balance->owe += $planPrice;
                $creditLimit->save();
                $balance->save();

                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $planPrice,
                    'BORROW',
                    $request->phone_number,
                    "Data borrowed: " . $planName,
                    $requestId,
                    'DATA_BORROW'
                );

            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ], 500);
            }

            $borrow = Borrow::create([
                'user_id' => $user->id,
                'amount' => $planPrice,
                'for' => "DATA",
                'status' => 'PENDING'
            ]);

            $payload = [
                'request_id'   => $requestId,
                'phone'        => $request->phone_number,
                'service_id'   => $request->network_id,
                'variation_id' => $request->variation_id,
            ];

            try {
                $response = Http::withToken(env('EBILLS_API_TOKEN'))
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/data', $payload);

                $responseData = $response->json();
            } catch (\Exception $e) {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_DATA',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                $creditLimit->limit_amount += $planPrice;
                $balance->owe -= $planPrice;
                $creditLimit->save();
                $balance->save();

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'rejected']);

                Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'FAILED'));

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t reach our endpoint service. Please check your internet connection and try again.'
                ], 500);
            }

            if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_DATA',
                    'message' => 'Borrowed data purchase successful',
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'SUCCESS',
                ]);

                $transaction->update([
                    'status' => 'SUCCESS',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'approved']);

                Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'SUCCESS'));

                return response()->json([
                    'status' => true, 
                    'message' => 'Data borrowed and purchased successfully',
                    'credit_limit_remaining' => $creditLimit->fresh()->limit_amount,
                    'amount_owed' => $balance->fresh()->owe
                ]);

            } else {
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'BORROW_DATA',
                    'message' => $responseData['message'] ?? 'API request failed',
                    'stack_trace' => json_encode($responseData, JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                $creditLimit->limit_amount += $planPrice;
                $balance->owe -= $planPrice;
                $creditLimit->save();
                $balance->save();

                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                $borrow->update(['status' => 'rejected']);

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
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($purchases);
    }
}