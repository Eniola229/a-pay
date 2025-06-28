<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPurchase;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Errors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DataPurchaseMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use App\Models\CreditLimit;
use App\Models\Borrow;


class BorrowDataController extends Controller
{
       public function showForm()
    {
        return view('borrow_data');
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
            $serviceId = strtolower($item['service_id']); // Convert service_id to lowercase
            return $serviceId === $networkId;
        });


        // Reformat data to match the expected structure
        $formatted = [];
        foreach ($filteredPlans as $plan) {
            $planCode = $plan['variation_id'] ?? uniqid(); // fallback if variation_id is missing
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
        $balance = Balance::where('user_id', $user->id)->first();
        $creditLimit = CreditLimit::where('user_id', $user->id)->first();

        if (!$creditLimit) {
            return response()->json(['status' => false, 'message' => 'ACTION NOT ALLOWED.'], 400);
        }

        // Verify PIN
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

        // Debug all variation_ids
       // Log::info('Available plan IDs: ' . json_encode($plans));
        //Log::info('Requested ID: ' . $variationId);

        // Find plan directly using the variation_id as key
        $planDetails = $plans[$variationId] ?? null; // Check if the plan exists

        // Log result
        //Log::info('Found plan: ' . json_encode($planDetails));

        if (!$planDetails) {
            return response()->json(['status' => false, 'message' => 'Invalid data plan selected.'], 400);
        }
        $planDetails = $plans[$variationId];
        $planPrice = $planDetails['price'];
        $planName = $planDetails['name'];

        // Check wallet balance
        if ($creditLimit->limit_amount < $planPrice) {
            return response()->json(['status' => false, 'message' => 'Insufficient Credit Limit.'], 400);
        }

        // Deduct balance
        $balance->owe += $planPrice;
        $creditLimit->limit_amount -= $planPrice;
        $balance->save();
        $creditLimit->save();

        // Create records
        $dataPurchase = DataPurchase::create([
            'user_id'      => $user->id,
            'phone_number' => $request->phone_number,
            'data_plan_id' => $request->variation_id,
            'network_id'   => $request->network_id,
            'amount'       => $planPrice,
            'status'       => 'PENDING'
        ]);

       //store borrow records
       $borrow = Borrow::create([
            'user_id' => $user->id,
            'amount' => $planPrice,
            'for' => "DATA", 
        ]);

        $transaction = Transaction::create([
            'user_id'     => $user->id,
            'amount'      => $planPrice,
            'beneficiary' => $request->phone_number,
            'description' => "Data purchase: " . $planName,
            'status'      => 'PENDING'
        ]);

        // Ebills API integration
        $apiToken = env('EBILLS_API_TOKEN'); // Set this in your .env
        $requestId = 'REQ_' . strtoupper(Str::random(12));

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
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t reach our endpoint service. Please check your internet connection and try again.'
            ], 500);
        }

        if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
            $transaction->update(['status' => 'SUCCESS']);
            $dataPurchase->update(['status' => 'SUCCESS']);
            $borrow->update(['status' => 'approved']);

            // Send success email
            Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'SUCCESS'));

            return response()->json(['status' => true, 'message' => 'Data purchased successfully']);
        } else {
            Log::error('Data purchase failed', ['response' => $responseData]);

            Errors::create([
                'title' => "Data",
                'error_message' => json_encode($responseData),
            ]);

            // Refund
            $creditLimit->increment('limit_amount', $planPrice);
            $transaction->update(['status' => 'ERROR']);
            $dataPurchase->update(['status' => 'FAILED']);
            $borrow->update(['status' => 'rejected']);

            $balance->owe -= $planPrice;
            $balance->save();

            // Send failure email
            Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planName, $planPrice, 'FAILED'));

            return response()->json([
                'status' => false,
                'message' => 'Data purchase failed. Your service provider may be unavailable. Please try again later.'
            ], 500);
        }
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
