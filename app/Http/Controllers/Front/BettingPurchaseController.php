<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataPurchase;
use App\Models\Betting;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\Errors;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BettingPurchaseMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use App\Services\CashbackService;



class BettingPurchaseController extends Controller
{
       public function showForm()
    {
        return view('betting');
    }

    public function buybetting(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|string',
            'service_id'   => 'required|string|in:1xBet,BangBet,Bet9ja,BetKing,BetLand,BetLion,BetWay,CloudBet,LiveScoreBet,MerryBet,NaijaBet,NairaBet,SupaBet',
            'amount' => 'required|integer',
            'pin'          => 'required|string|min:4|max:4'
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        // Verify PIN
        if (!Hash::check($request->pin, $balance->pin)) {
            return response()->json(['status' => false, 'message' => 'Invalid PIN.'], 400);
        }

        // Check wallet balance
        if ($balance->balance < $request->amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 400);
        }

        // Deduct balance
        $balance->balance -= $request->amount;
        $balance->save();

        // Create records
        $bettingPurchase = Betting::create([
            'user_id'      => $user->id,
            'customer_id' => $request->customer_id,
            'service_id' => $request->service_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'status'       => 'PENDING'
        ]);

        $transaction = Transaction::create([
            'user_id'     => $user->id,
            'amount'      => $request->amount,
            'beneficiary' => $request->customer_id . ' | ' . $request->service_id,
            'description' => "Betting Topup for " . $request->customer_id,
            'status'      => 'PENDING'
        ]);


        // Ebills API integration
        $apiToken = env('EBILLS_API_TOKEN'); // Set this in your .env
        $requestId = 'REQ_' . strtoupper(Str::random(12));

        $payload = [
            'request_id'   => $requestId,
            'service_id'   => $request->service_id,
            'customer_id' => $request->customer_id,
            'amount'        => $request->amount,
        ];

        try {
            $response = Http::withToken($apiToken)
                ->timeout(15)
                ->post('https://ebills.africa/wp-json/api/v2/betting', $payload);

            $responseData = $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'We couldn’t reach our endpoint service. Please check your internet connection and try again.'
            ], 500);
        }

        if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
            $transaction->update(['status' => 'SUCCESS']);
            $bettingPurchase->update(['status' => 'SUCCESS']);
            
            //For cashback
            $cashback = 0;
            $balance->balance += $cashback;
            $balance->save();
            $transaction->cash_back += $cashback;
            $transaction->save();

            //for mail
            $customerID = $bettingPurchase->customer_id;
            $serviceID = $bettingPurchase->service_id;
            $amountPaid = $bettingPurchase->amount;

            // Send success email
            Mail::to($user->email)->send(new BettingPurchaseMail($user, $customerID, $serviceID, $amountPaid, 'SUCCESS'));

            return response()->json(['status' => true, 'message' => 'Betting Topup successfully']);
        } else {
            Log::error('Betting purchase failed', ['response' => $responseData]);

            Errors::create([
                'title' => "Betting",
                'error_message' => json_encode($responseData),
            ]);

            // Refund
            $balance->increment('balance', $request->amount);
            $transaction->update(['status' => 'ERROR']);
            $bettingPurchase->update(['status' => 'FAILED']);

            //for mail
            $customerID = $bettingPurchase->customer_id;
            $serviceID = $bettingPurchase->service_id;
            $amountPaid = $bettingPurchase->amount;

            // Send failure email
            Mail::to($user->email)->send(new BettingPurchaseMail($user, $customerID, $serviceID, $amountPaid, 'FAILED'));

            return response()->json([
                'status' => false,
                'message' => 'Beting Topup failed. Your service provider may be unavailable. Please try again later.'
            ], 500);
        }
    }

    public function recentPurchases()
    {
        $purchases = DataPurchase::where('user_id', Auth::id())
            ->select('phone_number')
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($purchases);
    }

}
