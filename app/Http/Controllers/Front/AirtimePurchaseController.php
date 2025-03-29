<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AirtimePurchase;
use App\Models\Balance;
use App\Models\Errors;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\AirtimePurchaseMail;
use Illuminate\Support\Facades\Mail;

class AirtimePurchaseController extends Controller
{
   public function showForm()
    {
        return view('airtime');
    }
    public function buyAirtime(Request $request)
    {
        // Validate the request
        $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'network_id' => 'required|string|in:mtn,glo,airtel,etisalat',
            'pin' => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        // Verify the PIN
        if (!Hash::check($request->pin, $balance->pin)) {
            return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
        }

        // Check if the user has sufficient balance
        if (!$balance || $balance->balance < $request->amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
        }

        // Calculate cashback (3% of the amount)
        $cashback = $request->amount * 0.03;

        // Deduct the balance and add cashback
        $balance->balance -= $request->amount;
        $user->cashback_balance += $cashback;
        $balance->save();

        // Store the airtime purchase record
        $airtime = AirtimePurchase::create([
            'user_id' => $user->id,
            'phone_number' => $request->phone_number,
            'amount' => $request->amount,
            'network_id' => $request->network_id,
            'status' => 'PENDING'
        ]);

        // Store the transaction record
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'description' => "Airtime purchase for " . $request->phone_number,
            'status' => 'PENDING'
        ]);

        // Prepare VTU.ng API request
        $vtuUsername = env('VTU_NG_USERNAME');
        $vtuPassword = env('VTU_NG_PASSWORD');
        $baseUrl = 'https://paygold.ng/wp-json/api/v1/airtime';

        // Make the API request
        $response = Http::get($baseUrl, [
            'username' => $vtuUsername,
            'password' => $vtuPassword,
            'phone' => $request->phone_number,
            'network_id' => $request->network_id,
            'amount' => $request->amount
        ]);

        // Handle the API response
        if ($response->successful() && $response->json()['code'] === 'success') {
            $transaction->update(['status' => 'SUCCESS']);
            $airtime->update(['status' => 'SUCCESS']);

             // Send success email
            Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'SUCCESS'));

            return response()->json(['status' => true, 'message' => 'Airtime purchased successfully']);
        } else {
            // Log the error response
            Log::error('Airtime purchase failed', ['response' => $response->json()]);
            
            $errorData = is_array($response->json()) ? json_encode($response->json()) : $response->json();

            $error = Errors::create([
                'title' => "Airtime",
                'error_message' => $errorData,
            ]);
            // Refund the user
            $balance->balance += $request->amount;
            $user->cashback_balance -= $cashback;
            $balance->save();

            // Update transaction and airtime purchase status
            $airtime->update(['status' => 'FAILED']);
            $transaction->update(['status' => 'ERROR']);

            // Send failure email
            Mail::to($user->email)->send(new AirtimePurchaseMail($user, $transaction, 'FAILED'));

            return response()->json(['status' => false, 'message' => 'Airtime purchase failed. Your service provider may be unavailable. Please try again later.'], 500);
        }
    }

    public function recentPurchases()
    {
        $purchases = AirtimePurchase::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($purchases);
    }
}
