<?php

namespace App\Http\Controllers\Ussd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataController extends Controller
{
    public function getDataPlans($networkId)
    {
        $networkId = strtolower($networkId);
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');

        if ($response->failed()) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch data from provider.'], 500);
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
            return response()->json(['status' => false, 'message' => 'No plans found for this network.'], 404);
        }

        return response()->json(['status' => true, 'data' => $formatted]);
    }

}
