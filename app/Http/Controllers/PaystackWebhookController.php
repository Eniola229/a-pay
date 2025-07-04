<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use App\Mail\CreditAlertMail;
use Illuminate\Support\Facades\Mail;

class PaystackWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Verify the request from Paystack
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        if ($signature !== hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'charge.success' && isset($data['account_number'])) {
            $accountNumber = $data['account_number'];
            $amount = $data['amount'] / 100; // Convert from Kobo to Naira

            // Find the user with this virtual account number
            $user = User::where('account_number', $accountNumber)->first();

            if ($user) {
                $balance = Balance::where('user_id', $user->id)->first();
                
                if ($balance) {
                    $balance->balance += $amount;
                    $balance->save();
                } else {
                    Balance::create([
                        'user_id' => $user->id,
                        'balance' => $amount,
                    ]);
                }

                // Record transaction
                $transaction = Transaction::create([
                    'sender_id' => null, // Unknown sender
                    'recipient_id' => $user->id,
                    'amount' => $amount,
                    'status' => 'SUCCESS',
                    'description' => 'Credit Alert: ₦' . $amount,
                ]);

                // Send email notification
                Mail::to($user->email)->send(new CreditAlertMail($user, $amount, $transaction));

                Log::info("Money received: ₦$amount credited to user ID {$user->id}");
                return response()->json(['success' => 'Balance updated and email sent'], 200);
            }
        }

        return response()->json(['message' => 'Event received'], 200);
    }
}
