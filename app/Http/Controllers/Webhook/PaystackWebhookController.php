<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use App\Mail\CreditAlertMail;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();

        if (!$signature || $signature !== hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'))) {
            Log::warning("Invalid Paystack signature");
            return response()->json(['status' => 'invalid'], 401);
        }

        $data = $request->all();
        //Log::info("PAYSTACK WEBHOOK RECEIVED", $data);

        if (!isset($data['event'])) {
            return response()->json(['status' => 'ignored']);
        }

        if (in_array($data['event'], [
            'charge.success',
            'dedicated_account.transaction',
            'deposit.success'
        ])) {
            return $this->processDeposit($data['data']);
        }

        return response()->json(['status' => 'ignored']);
    }

    private function processDeposit($payload)
    {
        $reference = $payload['reference'] ?? null;
        $amount    = ($payload['amount'] ?? 0) / 100;
        $email     = $payload['customer']['email'] ?? null;
        $sender_name     = $payload['metadata']['sender_name'] ?? null;
        $sender_bank     = $payload['metadata']['sender_bank'] ?? null;

        if (!$reference) {
            Log::warning("Paystack webhook missing reference");
            return response()->json(['error' => 'missing reference'], 400);
        }

        if (!$email) {
            Log::warning("Webhook missing email. REF: {$reference}");
            return response()->json(['error' => 'no email'], 200);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Log::warning("No user found for email: {$email}");
            return response()->json(['error' => 'no user'], 200);
        }

        if (Transaction::where('reference', $reference)->exists()) {
            Log::info("Duplicate webhook ignored. REF: {$reference}");
            return response()->json(['status' => 'duplicate']);
        }

        DB::beginTransaction();
        try {
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$balance) {
                $balance = Balance::create([
                    'user_id' => $user->id,
                    'balance' => 0
                ]);
            }

            $newBalance = bcadd($balance->balance, $amount, 2);
            $balance->balance = $newBalance;
            $balance->save();

            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'amount'      => $amount,
                'cash_back'   => 0,
                'charges'     => 0,
                'beneficiary' => $user->mobile,
                'description' => "Wallet Top-up",
                'type'      => "CREDIT",
                'status'      => "SUCCESS",
                'reference'   => $reference,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook credit error: " . $e->getMessage());
            return response()->json(['status' => 'failed'], 500);
        }

        // WhatsApp alert
        try {
            $this->sendCreditAlertWhatsapp($user, $amount, $reference, $newBalance, $sender_name, $sender_bank);
        } catch (\Exception $e) {
            Log::error("WhatsApp credit alert failed: " . $e->getMessage());
        }

        // Email alert
        try {
            Mail::to($user->email)->send(new CreditAlertMail($user, $amount, $transaction));
        } catch (\Exception $e) {
            Log::error("Email credit alert failed: " . $e->getMessage());
        }

        return response()->json(['status' => 'success']);
    }

    private function sendCreditAlertWhatsapp($user, $amount, $reference, $newBalance, $sender_name, $sender_bank)
    {
        $msg = 
            "💳 *CREDIT ALERT*\n\n" .
            "Your A-Pay wallet has been funded.\n" .
            "From: {$sender_name} | {$sender_bank}\n" .
            "Amount: ₦" . number_format($amount, 2) . "\n" .
            "Ref: {$reference}\n" .
            "New Balance: ₦" . number_format($newBalance, 2) . "\n\n" .
            "Thank you for using A-Pay 💚";

        $this->sendMessage($user->mobile, $msg);
    }

    private function sendMessage($to, $body)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = 'whatsapp:' . env('TWILIO_W_NUMBER');

        if (!$sid || !$token || !$from) {
            Log::error('Missing Twilio credentials', [
                'sid' => $sid,
                'token' => $token,
                'from' => $from,
            ]);
            return;
        }

        try {
            $client = new Client($sid, $token);
            $client->messages->create("whatsapp:$to", [
                'from' => $from,
                'body' => $body,
            ]);
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp send failed: " . $e->getMessage());
        }
    }
}
