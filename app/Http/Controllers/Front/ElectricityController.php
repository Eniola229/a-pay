<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Logged;
use App\Models\ElectricityPurchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ElectricityPaymentReceipt;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use App\Services\WebTransactionService;

class ElectricityController extends Controller
{
    protected WebTransactionService $transactionService;

    public function __construct(WebTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function showForm()
    {
        return view('electricity');
    }

    public function verifyMeter(Request $request)
    {
        $request->validate([
            'meter_number' => 'required|string',
            'provider_id'  => 'required|string|in:abuja-electric,eko-electric,ibadan-electric,ikeja-electric,jos-electric,kaduna-electric,kano-electric,portharcourt-electric',
            'variation_id' => 'required|string|in:prepaid,postpaid',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                'Content-Type'  => 'application/json',
            ])->post('https://ebills.africa/wp-json/api/v2/verify/electricity', [
                'customer_id'  => $request->meter_number,
                'service_id'   => $request->provider_id,
                'variation_id' => $request->variation_id,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['code']) && $responseData['code'] === 'success') {
                    return response()->json([
                        'status' => true,
                        'message' => 'Meter verified successfully',
                        'data' => [
                            'customer_name' => $responseData['customer_name'] ?? 'N/A',
                            'customer_address' => $responseData['customer_address'] ?? 'N/A',
                            'meter_number' => $request->meter_number,
                        ]
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => $responseData['message'] ?? 'Meter verification failed'
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to verify meter. Please try again.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Meter verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Connection error. Please check your internet and try again.'
            ], 500);
        }
    }

    public function payElectricity(Request $request)
    {
        $request->validate([
            'meter_number' => 'required|string',
            'provider_id'  => 'required|string|in:abuja-electric,eko-electric,ibadan-electric,ikeja-electric,jos-electric,kaduna-electric,kano-electric,portharcourt-electric',
            'variation_id' => 'required|string|in:prepaid,postpaid',
            'amount'       => 'required|numeric|min:100',
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

            // Calculate total amount (amount + service fee + 60)
            $serviceFee = 39;
            $totalAmount = $request->amount + $serviceFee + 60;

            if ($request->amount < 1000) {
                return response()->json([
                    'status' => false,
                    'message' => 'BELOW THE ALLOWED MINIMUM AMOUNT OF NGN1000. Please, enter an amount equal or above the minimum amount.'
                ], 400);
            }

            // Check wallet balance
            if ($balance->balance < $totalAmount) {
                return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
            }

            // -----------------------
            // 2️⃣ Generate unique request ID
            // -----------------------
            $requestId = 'REQ_' . now()->format('YmdHis') . strtoupper(Str::random(12));

            // -----------------------
            // 3️⃣ Deduct balance via WebTransactionService (DEBIT)
            // -----------------------
            try {
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $totalAmount,
                    'DEBIT',
                    $request->meter_number,
                    "Electricity bill payment for " . $request->meter_number,
                    $requestId
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
            // 4️⃣ Store electricity purchase record (optional)
            // -----------------------
            // $electricityPurchase = ElectricityPurchase::create([
            //     'user_id'      => $user->id,
            //     'meter_number' => $request->meter_number,
            //     'provider_id'  => $request->provider_id,
            //     'amount'       => $request->amount,
            //     'service_fee'  => $serviceFee,
            //     'total_amount' => $totalAmount,
            //     'status'       => 'PENDING'
            // ]);

            // -----------------------
            // 5️⃣ Call the API
            // -----------------------
            try {
                $purchaseResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ])->post('https://ebills.africa/wp-json/api/v2/electricity', [
                    'request_id'   => $requestId,
                    'customer_id'  => $request->meter_number,
                    'service_id'   => $request->provider_id,
                    'variation_id' => $request->variation_id,
                    'amount'       => $request->amount,
                ]);
            } catch (\Exception $e) {
                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'ELECTRICITY',
                    'message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $totalAmount,
                    'CREDIT',
                    $request->meter_number,
                    'Refund for failed electricity payment',
                    'REFUND_' . $requestId
                );

                // Update original transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $electricityPurchase->update(['status' => 'FAILED']);

                // Prepare email details for failure
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $request->amount,
                    'token' => 0,
                    'units' => 0,
                    'status' => 'FAILED'
                ];

                try {
                    Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));
                } catch (\Exception $e) {
                    Log::error('Email send failed', ['error' => $e->getMessage()]);
                }

                return response()->json([
                    'status' => false,
                    'message' => 'We couldn\'t connect to our endpoint. Please check your internet connection and try again.'
                ], 500);
            }

            // -----------------------
            // 6️⃣ Check if the API request was successful
            // -----------------------
            if ($purchaseResponse->successful()) {
                $responseData = $purchaseResponse->json();

                // Check if the API response indicates success
                if (isset($responseData['code']) && $responseData['code'] === 'success') {
                    // Log success
                    Logged::create([
                        'user_id' => $user->id,
                        'for' => 'ELECTRICITY',
                        'message' => 'Electricity payment successful',
                        'stack_trace' => json_encode($responseData),
                        't_reference' => $requestId,
                        'from' => 'EBILLS',
                        'type' => 'SUCCESS',
                    ]);

                    // Update transaction with token and units
                    $transaction->update([
                        'description' => "Electricity bill payment for " . $request->meter_number . 
                                       ' | Token: ' . $responseData['token'] . 
                                       ' | Units: ' . $responseData['units'],
                        'status' => 'SUCCESS',
                        'reference' => $requestId
                    ]);

                    // $electricityPurchase->update(['status' => 'SUCCESS']);

                    // Prepare email details
                    $emailDetails = [
                        'user' => $user,
                        'meterNumber' => $request->meter_number,
                        'provider' => $request->provider_id,
                        'amount' => $request->amount,
                        'token' => $responseData['token'],
                        'units' => $responseData['units'],
                        'status' => 'SUCCESS'
                    ];

                    // Send email
                    try {
                        Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));
                    } catch (\Exception $e) {
                        Log::error('Email send failed', ['error' => $e->getMessage()]);
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Your electricity bill has been paid successfully. Please check your transaction history for the token.',
                        'token' => $responseData['token'],
                        'units' => $responseData['units'],
                        'balance' => $balance->fresh()->balance
                    ]);

                } else {
                    // Extract the error message from the API response
                    $errorMessage = $responseData['message'] ?? 'Payment failed due to an unknown error.';

                    // Log the error
                    Logged::create([
                        'user_id' => $user->id,
                        'for' => 'ELECTRICITY',
                        'message' => $errorMessage,
                        'stack_trace' => json_encode($responseData, JSON_PRETTY_PRINT),
                        't_reference' => $requestId,
                        'from' => 'EBILLS',
                        'type' => 'FAILED',
                    ]);

                    // Refund the user (CREDIT)
                    $refundTransaction = $this->transactionService->createTransaction(
                        $user,
                        $totalAmount,
                        'CREDIT',
                        $request->meter_number,
                        'Refund for failed electricity payment',
                        'REFUND_' . $requestId
                    );

                    // Update transaction status
                    $transaction->update([
                        'status' => 'ERROR',
                        'reference' => $requestId
                    ]);

                    // $electricityPurchase->update(['status' => 'FAILED']);

                    // Prepare email details for failure
                    $emailDetails = [
                        'user' => $user,
                        'meterNumber' => $request->meter_number,
                        'provider' => $request->provider_id,
                        'amount' => $request->amount,
                        'token' => 0,
                        'units' => 0,
                        'status' => 'FAILED'
                    ];

                    try {
                        Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));
                    } catch (\Exception $e) {
                        Log::error('Email send failed', ['error' => $e->getMessage()]);
                    }

                    return response()->json(['status' => false, 'message' => $errorMessage], 500);
                }
            } else {
                // Handle HTTP request errors
                $responseData = $purchaseResponse->json();
                $errorMessage = $responseData['message'] ?? 'Failed to connect to the payment gateway.';

                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'ELECTRICITY',
                    'message' => $errorMessage,
                    'stack_trace' => json_encode($responseData, JSON_PRETTY_PRINT),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund the user (CREDIT)
                $refundTransaction = $this->transactionService->createTransaction(
                    $user,
                    $totalAmount,
                    'CREDIT',
                    $request->meter_number,
                    'Refund for failed electricity payment',
                    'REFUND_' . $requestId
                );

                // Update transaction status
                $transaction->update([
                    'status' => 'ERROR',
                    'reference' => $requestId
                ]);

                // $electricityPurchase->update(['status' => 'FAILED']);

                // Prepare email details for failure
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $request->amount,
                    'token' => 0,
                    'units' => 0,
                    'status' => 'FAILED'
                ];

                try {
                    Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));
                } catch (\Exception $e) {
                    Log::error('Email send failed', ['error' => $e->getMessage()]);
                }

                return response()->json([
                    'status' => false,
                    'message' => 'An error occurred. Please try again later.'
                ], 500);
            }
        });
    }
}