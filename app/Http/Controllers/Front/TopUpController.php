<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance; 
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TopUpController extends Controller
{
   // Display the top-up page
    public function index()
    {
        $balance = Balance::where('user_id', Auth::id())->first();
        return view('topup', compact('balance'));
    }

    // Initialize the payment transaction with Paystack
    public function initialize(Request $request)
    {

        $request->merge([
            'amount' => str_replace([',', '.'], '', $request->amount)
        ]);        

        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $email = Auth::user()->email;
        $amount = $request->amount * 100; // Convert Naira to kobo (if using NGN)
        $callback_url = route('topup.callback');

        $client = new Client();
        $response = $client->post('https://api.paystack.co/transaction/initialize', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json'
            ],
            'json' => [
                'email'        => $email,
                'amount'       => $amount,
                'callback_url' => $callback_url
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['status'] && isset($body['data'])) {
            // Redirect user to Paystack payment page
            $transaction = Transaction::create([
                'user_id'     => Auth::user()->id,
                'beneficiary' => Auth::user()->mobile,
                'amount'      => $request->amount, 
                'description' => 'Wallet Top-up',
                'status'      => 'PENDING'
            ]);

            return redirect($body['data']['authorization_url']);
        } else {
            return back()->with('error', 'Payment initialization failed. Please try again.');
        }
    }

    // Callback endpoint after payment
    public function callback(Request $request)
    {
        $reference = $request->reference;
        if (!$reference) {
            return redirect()->route('topup')->with('error', 'No payment reference provided.');
        }

        $client = new Client();
        $response = $client->get("https://api.paystack.co/transaction/verify/{$reference}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['status'] && $body['data']['status'] == 'success') {
            // Payment was successful, update the user's balance
            $amount = $body['data']['amount'] / 100; // Convert back to Naira
            $balance = Balance::firstOrCreate(
                ['user_id' => Auth::id()],
                ['balance' => 0, 'pin' => ''] 
            );
           $transaction = Transaction::where('user_id', Auth::id())
            ->where('description', 'Wallet Top-up')
            ->latest()
            ->first();
            $transaction->status = "SUCCESS";
            $transaction->save();
            $balance->balance += $amount;
            $balance->save();

            return redirect()->route('topup')->with('success', 'Top up successful!');
        } else {
            $transaction = Transaction::where('user_id', Auth::id())
            ->where('description', 'Wallet Top-up')
            ->latest()
            ->first();
            $transaction->status = "ERROR";
            $transaction->save();
            return redirect()->route('topup')->with('error', 'Payment failed, please try again.');
        }
    }

    // Endpoint to return current balance (for auto redraw)
    public function getBalance()
    {
        $balance = Balance::where('user_id', Auth::id())->first();
        return response()->json(['balance' => $balance ? $balance->balance : 0]);
    }
}
