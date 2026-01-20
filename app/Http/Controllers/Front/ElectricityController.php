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

            // Calculate total amount with transaction fee
            $transactionFee = 99;
            $totalAmount = $request->amount + $transactionFee;

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
            // 3️⃣ Deduct balance via WebTransactionService
            // -----------------------
            try {
                // Debit only the electricity amount (not including fee)
                $transaction = $this->transactionService->createTransaction(
                    $user,
                    $request->amount,
                    'DEBIT',
                    $request->meter_number,
                    "Electricity bill payment for meter " . $request->meter_number,
                    $requestId,
                    null,
                    $transactionFee
                );
                
                // Debit the transaction fee separately
                $feeTransaction = $this->transactionService->createTransaction(
                    $user,
                    $transactionFee,
                    'DEBIT',
                    'SYSTEM',
                    "Transaction fee for electricity purchase - Meter: " . $request->meter_number,
                    'FEE_' . $requestId,
                    null,
                    0
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
            // 4️⃣ Call the API
            // -----------------------
            try {
                $purchaseResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ])->timeout(15)->post('https://ebills.africa/wp-json/api/v2/electricity', [
                    'request_id'   => $requestId,
                    'customer_id'  => $request->meter_number,
                    'service_id'   => $request->provider_id,
                    'variation_id' => $request->variation_id,
                    'amount'       => $request->amount,
                ]);

                $responseData = $purchaseResponse->json();
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

                // Refund both transactions
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $request->meter_number,
                    "Refund for electricity purchase failed - Provider unreachable for meter {$request->meter_number}",
                    "REFUND_" . $requestId
                );
                
                $this->transactionService->refundTransaction(
                    $feeTransaction,
                    $balance,
                    'FEE_' . $requestId,
                    'SYSTEM',
                    "Refund of transaction fee - Provider unreachable for meter {$request->meter_number}",
                    "REFUND_FEE_" . $requestId
                );

                // Prepare email details for failure
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $totalAmount,
                    'token' => 'N/A',
                    'units' => 'N/A',
                    'customer_address' => 'N/A',
                    'customer_name_m' => 'N/A',
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
            // 5️⃣ Check if the API request was successful
            // -----------------------
            if ($purchaseResponse->successful() && ($responseData['code'] ?? '') === 'success') {
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

                // Get token and units from nested 'data' object
                $token = $responseData['data']['token'] ?? 'N/A';
                $units = $responseData['data']['units'] ?? 'Not Provided';

                // Mark both transactions as SUCCESS
                $this->transactionService->markTransactionSuccess(
                    $transaction,
                    "Electricity bill payment for meter {$request->meter_number} | Token: {$token} | Units: {$units}",
                    $requestId,
                    $request->meter_number
                );
                
                $this->transactionService->markTransactionSuccess(
                    $feeTransaction,
                    "Transaction fee for electricity purchase - Meter: {$request->meter_number} | Token: {$token}",
                    'FEE_' . $requestId,
                    'SYSTEM'
                );

                // Prepare email details
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $totalAmount,
                    'token' => $token,
                    'units' => $units,
                    'customer_address' => $responseData['data']['customer_address'] ?? 'N/A',
                    'customer_name_m' => $responseData['data']['customer_name'] ?? 'N/A',
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
                    'token' => $token,
                    'units' => $units,
                    'balance' => $balance->fresh()->balance
                ]);

            } else {
                // Extract the error message from the API response
                $errorMessage = $responseData['message'] ?? 'Payment failed due to an unknown error.';

                // Log the error
                Logged::create([
                    'user_id' => $user->id,
                    'for' => 'ELECTRICITY',
                    'message' => json_encode($responseData),
                    'stack_trace' => json_encode($responseData),
                    't_reference' => $requestId,
                    'from' => 'EBILLS',
                    'type' => 'FAILED',
                ]);

                // Refund both transactions
                $this->transactionService->refundTransaction(
                    $transaction,
                    $balance,
                    $requestId,
                    $request->meter_number,
                    "Refund for electricity purchase - Payment unsuccessful for meter {$request->meter_number}",
                    "REFUND_" . $requestId
                );
                
                $this->transactionService->refundTransaction(
                    $feeTransaction,
                    $balance,
                    'FEE_' . $requestId,
                    'SYSTEM',
                    "Refund of transaction fee - Payment unsuccessful for meter {$request->meter_number}",
                    "REFUND_FEE_" . $requestId
                );

                // Prepare email details for failure
                $emailDetails = [
                    'user' => $user,
                    'meterNumber' => $request->meter_number,
                    'provider' => $request->provider_id,
                    'amount' => $totalAmount,
                    'token' => 'N/A',
                    'units' => 'N/A',
                    'customer_address' => $responseData['data']['customer_address'] ?? 'N/A',
                    'customer_name_m' => $responseData['data']['customer_name'] ?? 'N/A',
                    'status' => 'FAILED'
                ];

                try {
                    Mail::to($user->email)->send(new ElectricityPaymentReceipt($emailDetails));
                } catch (\Exception $e) {
                    Log::error('Email send failed', ['error' => $e->getMessage()]);
                }

                return response()->json(['status' => false, 'message' => $errorMessage], 500);
            }
        });
    }
}