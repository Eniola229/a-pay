<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Errors;
use App\Models\ElectricityPurchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ElectricityPaymentReceipt;
use Illuminate\Http\Client\RequestException;


class ElectricityController extends Controller
{
    public function showForm()
    {
        return view('electricity');
    }

    public function payElectricity(Request $request)
    {
        $request->validate([
            'meter_number' => 'required|string',
            'provider_id'  => 'required|string|in:abuja-electric,eko-electric,ibadan-electric,ikeja-electric,jos-electric,kaduna-electric,kano-electric,portharcourt-electric',
            'variation_id'  => 'required|string|in:prepaid,postpaid',
            'amount'       => 'required|numeric|min:100',
            'pin'          => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        // Verify PIN
        if (!Hash::check($request->pin, $balance->pin)) {
            return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
        }

        // Calculate total amount (amount + service fee + 10)
        $serviceFee = 39; // Fixed service fee
        $totalAmount = $request->amount + $serviceFee + 60;

        if ($request->amount < 1000) {
            return response()->json(['status' => false, 'message' => 'BELOW THE ALLOWED MINIMUM AMOUNT OF NGN1000. Please, enter an amount equal or above the minimum amount.'], 400);
        }

        // Check wallet balance
        if ($balance->balance < $totalAmount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
        }

        // Deduct balance and save
        $balance->balance -= $totalAmount;
        $balance->save();

        // Store electricity purchase record
        $electricityPurchase = ElectricityPurchase::create([
            'user_id'      => $user->id,
            'meter_number' => $request->meter_number,
            'provider_id'  => $request->provider_id,
            'amount'       => $request->amount,
            'service_fee'  => $serviceFee,
            'total_amount' => $totalAmount,
            'status'       => 'PENDING'
        ]);

        // Store transaction record
        $transaction = Transaction::create([
            'user_id'     => $user->id,
            'amount'      => $totalAmount,
            'beneficiary' => $request->meter_number,
            'description' => "Electricity bill payment for " . $request->meter_number,
            'status'      => 'PENDING'
        ]);

        // Prepare API request to pay electricity bill
        $vtuUsername = env('VTU_NG_USERNAME');
        $vtuPassword = env('VTU_NG_PASSWORD');
        $baseUrl = 'https://ebills.africa/wp-json/api/v2/electricity';

        // Generate a unique request_id
        $requestId = 'req_' . uniqid();

        // Call the API
        try {
            $purchaseResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                'Content-Type'  => 'application/json',
            ])->post($baseUrl, [
                'request_id'   => $requestId,
                'customer_id'  => $request->meter_number,
                'service_id'   => $request->provider_id,
                'variation_id' => $request->variation_id,
                'amount'       => $request->amount,
            ]);
        } catch (RequestException $e) {
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t connect to our endpoint. Please check your internet connection and try again.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t connect to our endpoint. Please check your internet connection and try again.'
            ], 500);
        }

        // Check if the API request was successful
        if ($purchaseResponse->successful()) {
            $responseData = $purchaseResponse->json();

            // Check if the API response indicates success
            if (isset($responseData['code']) && $responseData['code'] === 'success') {
              // Store transaction record
                $transaction->update([
                        'amount'      => $totalAmount,
                        'description' => "Electricity bill payment for " . $request->meter_number . ' | Token: ' . $responseData['token'] . ' | Units: ' . $responseData['units'],
                        'status'      => 'SUCCESS'
                    ]);
                
                $electricityPurchase->update(['status' => 'SUCCESS']);

                // Prepare email details
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $request->amount,
                    'status' => 'SUCCESS'
                ];



                // Send email
                Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));

                return response()->json(['status' => true, 'message' => 'EYour electricity bill has been paid successfully. Please check your transaction history for the token.']);
            } else {
                // Extract the error message from the API response
                $errorMessage = $responseData['message'] ?? 'Payment failed due to an unknown error.';

                // Convert response array to JSON before storing it
                $errorData = json_encode($responseData);

                // Save error details
                Errors::create([
                    'title' => "Electricity",
                    'error_message' => $errorData,
                ]);

                // Refund if payment fails
                $balance->increment('balance', $totalAmount);

                // Update transaction statuses
                $transaction->update(['status' => 'ERROR']);
                $electricityPurchase->update(['status' => 'FAILED']);

                Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));

                return response()->json(['status' => false, 'message' => $errorMessage], 500);
            }
        } else {
            // Handle HTTP request errors (e.g., network issues, server down)
            $responseData = $purchaseResponse->json(); // Store it first

            $errorMessage = $responseData['message'] ?? 'Failed to connect to the payment gateway.';
            $errorData = json_encode($responseData);



            // Save error details
            Errors::create([
                'title' => "Electricity",
                'error_message' => $errorData,
            ]);

            // Refund if payment fails
            $balance->increment('balance', $totalAmount);

            // Update transaction statuses
            $transaction->update(['status' => 'ERROR']);
            $electricityPurchase->update(['status' => 'FAILED']);

            $user_details = Auth::user();
            $emailDetails = [
                'user' => $user_details,
                'meterNumber' => $request->meter_number,
                'provider' => $request->provider_id,
                'amount' => $request->amount,
                'status' => 'FAILED'
            ];

            Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));

            return response()->json(['status' => false, 'message' => "An error occurred. Please try again later."], 500);
        }
    }

    }