<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance; 
use App\Models\Transaction; 
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;

class WithdrawalController extends Controller
{
    // Display the withdrawal page with current balance
    public function index()
    {
        $balance = Balance::where('user_id', Auth::id())->first();
        return view('withdraw', compact('balance'));
    }

// Process the withdrawal request
public function processWithdrawal(Request $request)
{
    // Validate input including the PIN field
    $request->validate([
        'amount'       => 'required|numeric|min:100',
        'bank_account' => 'required|string',
        'bank_code'    => 'required|string',
        'pin'          => 'required|string'
    ]);

    // Get the user's balance record
    $balanceRecord = Balance::where('user_id', Auth::id())->first();
    if (!$balanceRecord) {
        return response()->json(['status' => false, 'message' => 'Balance record not found.'], 404);
    }

    // if ($request->amount < 100) {
    //     return response()->json(['status' => false, 'message' => 'You cannot withdraw less than 100.'], 404);    
    // }

    // **Declare $transaction here**
    $transaction = null;

    try {
        // Create a pending transaction record
        $transaction = Transaction::create([
            'user_id' => Auth::id(),
            'amount' => $request->amount,
            'status' => "PENDING"
        ]);

        // Check if the provided PIN matches the stored hashed PIN
        if (!Hash::check($request->pin, $balanceRecord->pin)) {
            $transaction->status = "ERROR";
            $transaction->description = "Withdrawal Failed due to Incorrect Pin";
            $transaction->save();
            return response()->json(['status' => false, 'message' => 'Incorrect PIN.'], 400);
        }

        $Removeamount = $request->amount + 10;

        // Ensure sufficient funds exist
        if ($balanceRecord->balance < $Removeamount) {
            $transaction->status = "ERROR";
            $transaction->description = "Withdrawal Failed due to Insufficient balance";
            $transaction->save();
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
        }

        $client = new Client();
        $amountInKobo = $request->amount * 100; // Convert Naira to kobo

        // Create transfer recipient on Paystack (cache recipient_code if possible)
        $recipient_code = $this->createRecipient($request);

        // Initiate the transfer via Paystack
        $response = $client->post('https://api.paystack.co/transfer', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json'
            ],
            'json' => [
                'source'    => 'balance',
                'amount'    => $amountInKobo,
                'recipient' => $recipient_code,
                'reason'    => 'Withdrawal for user ' . Auth::id(),
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        

        if ($body['status']) {
            // Deduct the amount from the user's balance upon successful initiation
            $balanceRecord->balance -= $Removeamount;
            $balanceRecord->save();

            return response()->json(['status' => true, 'message' => 'Withdrawal initiated successfully']);
        } else {
            $transaction->status = "ERROR";
            $transaction->description = "Unknown Error";
            $transaction->save();
            return response()->json(['status' => false, 'message' => 'Withdrawal failed, please try again.'], 500);
        }
    } catch (\Exception $e) {
        if ($transaction) {
            $transaction->status = "ERROR";
            $transaction->description = "Withdrawal failed due to system error";
            $transaction->save();
        }
        return response()->json(['status' => false, 'message' => 'An error occurred. Please double-check the selected bank or your internet connection.'], 500);
    }
}


// Create a transfer recipient on Paystack using user and bank details
private function createRecipient(Request $request)
{
    $client = new Client();
    $transaction = null; // Declare transaction variable

    try {
        $response = $client->post('https://api.paystack.co/transferrecipient', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json'
            ],
            'json' => [
                'type'           => 'nuban',
                'name'           => Auth::user()->name,
                'account_number' => $request->bank_account,
                'bank_code'      => $request->bank_code,
                'currency'       => 'NGN'
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        // Retrieve the most recent transaction related to the user
        $transaction = Transaction::where('user_id', Auth::id())->latest()->first();

        if ($body['status']) {
            // Ensure transaction exists before updating it
            if ($transaction) {
                $transaction->status = "SUCCESS";
                $transaction->description = "Withdrawal successfully";
                $transaction->save();
            }

            // Return the recipient code
            return $body['data']['recipient_code'];
        } else {
            if ($transaction) {
                $transaction->status = "ERROR";
                $transaction->description = "Unknown Error 2.";
                $transaction->save();
            }
            throw new \Exception('Could not create recipient');
        }
    } catch (\Exception $e) {
        // Check if the transaction exists before updating it
        if ($transaction) {
            $transaction->status = "ERROR";
            $transaction->description = "Unknown Error 3.";
            $transaction->save();
        }
        throw new \Exception('Error creating recipient: ' . $e->getMessage());
    }
}

    // Return the current balance as JSON (used for auto-refreshing the balance display)
    public function getBalance()
    {
        $balance = Balance::where('user_id', Auth::id())->first();
        return response()->json(['balance' => $balance ? $balance->balance : 0]);
    }
}
