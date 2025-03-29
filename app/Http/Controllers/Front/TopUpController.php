<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance; 
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;

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
        $request->validate([
            'amount' => 'required|numeric|min:100'
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
            $balance->balance += $amount;
            $balance->save();

            return redirect()->route('topup')->with('success', 'Top up successful!');
        } else {
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
