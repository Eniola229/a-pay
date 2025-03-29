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


class DataPurchaseController extends Controller
{
       public function showForm()
    {
        return view('data');
    }
    // Fetch data plans from the external API
    public function getDataPlans($networkId)
    {
        // Force network id to lowercase
        $networkId = strtolower($networkId);

        // Static pricing data for each network with description and price
        $plans = [
            'mtn' => [
                '500'       => ['name' => 'MTN Data 500MB – 30 Days', 'price' => 200],
                'M1024'     => ['name' => 'MTN Data 1GB – 30 Days', 'price' => 300],
                'M2024'     => ['name' => 'MTN Data 2GB – 30 Days', 'price' => 600],
                '3000'      => ['name' => 'MTN Data 3GB – 30 Days', 'price' => 900],
                '5000'      => ['name' => 'MTN Data 5GB – 30 Days', 'price' => 1500],
                '10000'     => ['name' => 'MTN Data 10GB – 30 Days', 'price' => 2950],
                'mtn-2-5gb-600' => ['name' => 'MTN Data 5GB – 7 Days', 'price' => 1470],
                'mtn-11gb-3500' => ['name' => 'MTN Data 11GB – 30 Days', 'price' => 3430],
                'mtn-13gb-4000' => ['name' => 'MTN Data 13GB – 30 Days', 'price' => 3920],
                'mtn-25gb-6500' => ['name' => 'MTN Data 25GB + Youtube – 30 Days', 'price' => 6370],
                'mtn-40gb-11000' => ['name' => 'MTN Data 40GB – 30 Days', 'price' => 10780],
                'mtn-75gb-16000' => ['name' => 'MTN Data 75GB – 30 Days', 'price' => 15680],
                'mtn-75gb-20000' => ['name' => 'MTN Data 75GB – 60 Days', 'price' => 19600],
                'mtn-120gb-30000' => ['name' => 'MTN Data 120GB – 60 Days', 'price' => 29400],
                'mtn-150gb-50000' => ['name' => 'MTN Data 150GB – 90 Days', 'price' => 49000],
                'mtn-5gb-1500' => ['name' => 'MTN Data 5GB – 7 Days', 'price' => 1470],
                'mtn-250gb-75000' => ['name' => 'MTN Data 250GB – 900 Days', 'price' => 73500],
                'mtn-400gb-120000' => ['name' => 'MTN Data 400GB – 365 Days', 'price' => 117600],
                'mtn-1000gb-250000' => ['name' => 'MTN Data 1000GB – 365Days', 'price' => 245000],
                '2000gb-450000' => ['name' => 'MTN Data 2000GB – 365 Days', 'price' => 441000]
            ],
            'glo' => [
                'G500'    => ['name' => 'Glo Data 500MB (Gift) – 30 Days', 'price' => 200],
                'G1000'   => ['name' => 'Glo Data 1GB (Gift) – 30 Days', 'price' => 329],
                'G2000'   => ['name' => 'Glo Data 2GB (Gift) – 30 Days', 'price' => 659],
                'G3000'   => ['name' => 'Glo Data 3GB (Gift) – 30 Days', 'price' => 989],
                'G5000'   => ['name' => 'Glo Data 5GB (Gift) – 30 Days', 'price' => 1649],
                'G10000'  => ['name' => 'Glo Data 10GB (Gift) – 30 Days', 'price' => 3299],
                'G1350'   => ['name' => 'Glo Data 1.35GB – 14 Days', 'price' => 500],
                'G2900'   => ['name' => 'Glo Data 2.9GB – 30 Days', 'price' => 1000],
                'G5800'   => ['name' => 'Glo Data 5.8GB – 30 Days', 'price' => 2000],
                'G7700'   => ['name' => 'Glo Data 7.7GB – 30 Days', 'price' => 2449],
                'G10000B' => ['name' => 'Glo Data 10GB – 30 Days', 'price' => 2949],
                'G13250'  => ['name' => 'Glo Data 13.25GB – 30 Days', 'price' => 3889],
                'G18250'  => ['name' => 'Glo Data 18.25GB – 30 Days', 'price' => 4849],
                'G29500'  => ['name' => 'Glo Data 29.5GB – 30 Days', 'price' => 7799],
                'G50000'  => ['name' => 'Glo Data 50GB – 30 Days', 'price' => 9899]
            ],
            'airtel' => [
                'AIRTEL500MB'  => ['name' => 'Airtel Data 500MB (Gift) – 30 Days', 'price' => 429],
                'AIRTEL1GB'    => ['name' => 'Airtel Data 1GB (Gift) – 30 Days', 'price' => 729],
                'AIRTEL2GB'    => ['name' => 'Airtel Data 2GB (Gift) – 30 Days', 'price' => 1459],
                'AIRTEL5GB'    => ['name' => 'Airtel Data 5GB (Gift) – 30 Days', 'price' => 3649],
                'AIRTEL10GB'   => ['name' => 'Airtel Data 10GB (Gift) – 30 Days', 'price' => 7299],
                'AIRTEL15GB'   => ['name' => 'Airtel Data 15GB (Gift) – 30 Days', 'price' => 10939],
                'AIRTEL20GB'   => ['name' => 'Airtel Data 20GB (Gift) – 30 Days', 'price' => 14580],
                'AIRTEL750MB'  => ['name' => 'Airtel Data 750MB – 14 Days', 'price' => 545],
                'AIRTEL1GB1D'  => ['name' => 'Airtel Data 1GB – 1 Day', 'price' => 400],
                'AIRTEL1.5GB'  => ['name' => 'Airtel Data 1.5GB – 30 Days', 'price' => 1100],
                'AIRTEL2GBB'   => ['name' => 'Airtel Data 2GB – 30 Days', 'price' => 1289],
                'AIRTEL3GB'    => ['name' => 'Airtel Data 3GB – 30 Days', 'price' => 1639],
                'AIRTEL4.5GB'  => ['name' => 'Airtel Data 4.5GB – 30 Days', 'price' => 2189],
                'AIRTEL6GB'    => ['name' => 'Airtel Data 6GB – 7 Days', 'price' => 1639],
                'AIRTEL10GBB'  => ['name' => 'Airtel Data 10GB – 30 Days', 'price' => 3289],
                'AIRTEL20GBB'  => ['name' => 'Airtel Data 20GB – 30 Days', 'price' => 5489],
                'AIRTEL40GB'   => ['name' => 'Airtel Data 40GB – 30 Days', 'price' => 10799]
            ],
            'etisalat' => [
                '9MOB1GB'  => ['name' => '9mobile Data 1GB – 30 Days', 'price' => 989],
                '9MOB2.5GB' => ['name' => '9mobile Data 2.5GB – 30 Days', 'price' => 1989],
                '9MOB11.5GB' => ['name' => '9mobile Data 11.5GB – 30 Days', 'price' => 7969],
                '9MOB15GB'   => ['name' => '9mobile Data 15GB – 30 Days', 'price' => 9899]
            ]
        ];


        if (!isset($plans[$networkId])) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid network id provided.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $plans[$networkId]
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
            'description' => "Data purchase for " . $request->phone_number,
            'status'      => 'PENDING'
        ]);

        // VTU.ng API credentials
        $vtuUsername = env('VTU_NG_USERNAME');
        $vtuPassword = env('VTU_NG_PASSWORD');
        $baseUrl = 'https://paygold.ng/wp-json/api/v1/data';

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
        $purchaseResponse = Http::get($url);
        $responseData = $purchaseResponse->json();

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
