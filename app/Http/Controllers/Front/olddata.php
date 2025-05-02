<?php

namespace App\Http\Controllers\Front;

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


class DataPurchaseController extends Controller
{
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
            'network_id'   => 'required|string|in:mtn,glo,airtel,etisalat',
            'variation_id' => 'required|string',
            'pin'          => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

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

        // Validate variation_id
        if (!isset($plans[$request->variation_id])) {
            return response()->json(['status' => false, 'message' => 'Invalid data plan selected.'], 400);
        }

        $planDetails = $plans[$request->variation_id];
        $planPrice = $planDetails['price'];
        $planDetails = $planDetails['name'];

        // Check wallet balance
        if ($balance->balance < $planPrice) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
        }

        // Deduct balance
        $balance->balance -= $planPrice;
        $balance->save();

        // Create records
        $dataPurchase = DataPurchase::create([
            'user_id'      => $user->id,
            'phone_number' => $request->phone_number,
            'data_plan_id' => $request->variation_id,
            'network_id'   => $request->network_id,
            'amount'       => $planPrice,
            'status'       => 'PENDING'
        ]);

        $transaction = Transaction::create([
            'user_id'     => $user->id,
            'amount'      => $planPrice,
            'beneficiary' => $request->phone_number,
            'description' => "Data purchase: " . $planDetails,
            'status'      => 'PENDING'
        ]);

        // VTU.ng API credentials
        $vtuUsername = env('VTU_NG_USERNAME');
        $vtuPassword = env('VTU_NG_PASSWORD');
        $baseUrl = 'https://paygold.ng/wp-json/api/v1/data';

        try {
            // Construct the full URL with query parameters
            $url = sprintf(
                '%s?username=%s&password=%s&phone=%s&network_id=%s&variation_id=%s',
                $baseUrl,
                urlencode($vtuUsername),
                urlencode($vtuPassword),
                urlencode($request->phone_number),
                urlencode($request->network_id),
                urlencode($request->variation_id)
            );

            // Make the GET request
            $purchaseResponse = Http::timeout(10)->get($url); // Optional: set timeout
            $responseData = $purchaseResponse->json();            

        } catch (RequestException $e) {
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t reach our end point service. Please check your internet connection and try again.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t reach our end point service. Please check your internet connection and try again.'
            ], 500);
        }

        if ($purchaseResponse->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
            $transaction->update(['status' => 'SUCCESS']);
            $dataPurchase->update(['status' => 'SUCCESS']);

            // Send success email
            Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planDetails['name'], $planPrice, 'SUCCESS'));

            return response()->json(['status' => true, 'message' => 'Data purchased successfully']);
        } else {
            // Log the full response for debugging
            Log::error('Data purchase failed', ['response' => $responseData]);

            // Convert the array response to JSON string before storing in DB
            $errorData = json_encode($responseData);

            $error = Errors::create([
                'title' => "Data",
                'error_message' => $errorData,
            ]);

            // Refund the user if purchase fails
            $balance->increment('balance', $planPrice);

            $transaction->update(['status' => 'ERROR']);
            $dataPurchase->update(['status' => 'FAILED']);

            // Send failure email
            Mail::to($user->email)->send(new DataPurchaseMail($user, $request->phone_number, $planDetails['name'], $planPrice, 'FAILED'));
            
            return response()->json(['status' => false, 'message' => 'Data purchase failed. Your service provider may be unavailable. Please try again later.'], 500);
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
