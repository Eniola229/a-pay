<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\Logged;
use App\Models\Transaction;
use App\Models\WhatsappMessage;
use App\Mail\CreditAlertMail;
use App\Services\TransactionService;
use App\Services\SmsService;

class PaystackWebhookController extends Controller
{
    protected $transactionService;
    protected $smsService;

    public function __construct(SmsService $smsService, TransactionService $transactionService)
    {
        $this->smsService = $smsService;
        $this->transactionService = $transactionService;
    }

    public function handle(Request $request)
    {
        // Verify signature
        $signature = $request->header('x-paystack-signature');
        $payload   = $request->getContent();

        if (!$signature || $signature !== hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'))) {
            Logged::create([
                'user_id' => null,
                'for' => 'PAYSTACK_WEBHOOK',
                'message' => 'Invalid Paystack signature',
                'stack_trace' => json_encode(['signature' => $signature]),
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'FAILED',
            ]);
            return response()->json(['status' => 'invalid'], 401);
        }

        $data = $request->all();

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
        $sender_name = $payload['authorization']['sender_name'] ?? null;
        $sender_bank = $payload['authorization']['sender_bank'] ?? null;

        if (!$reference) {
            Logged::create([
                'user_id' => null,
                'for' => 'PAYSTACK_WEBHOOK',
                'message' => 'Paystack webhook missing reference',
                'stack_trace' => json_encode($payload),
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'FAILED',
            ]);
            return response()->json(['error' => 'missing reference'], 400);
        }

        if (!$email) {
            Logged::create([
                'user_id' => null,
                'for' => 'PAYSTACK_WEBHOOK',
                'message' => "Webhook missing email. REF: {$reference}",
                'stack_trace' => json_encode($payload),
                't_reference' => $reference,
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'FAILED',
            ]);
            return response()->json(['error' => 'no email'], 200);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            Logged::create([
                'user_id' => null,
                'for' => 'PAYSTACK_WEBHOOK',
                'message' => "No user found for email: {$email}",
                'stack_trace' => json_encode(['email' => $email, 'reference' => $reference]),
                't_reference' => $reference,
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'FAILED',
            ]);
            return response()->json(['error' => 'no user'], 200);
        }

        if (Transaction::where('reference', $reference)->exists()) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'PAYSTACK_WEBHOOK',
                'message' => "Duplicate webhook ignored. REF: {$reference}",
                'stack_trace' => json_encode(['reference' => $reference]),
                't_reference' => $reference,
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'INFO',
            ]);
            return response()->json(['status' => 'duplicate']);
        }

        try {
            // Use TransactionService to create credit transaction
            $transaction = $this->transactionService->createTransaction(
                $user,
                $amount,
                'CREDIT',
                $user->mobile,
                "Wallet Top-up"
            );

            // Update transaction with reference and mark as success
            $transaction->update([
                'reference' => $reference,
                'status' => 'SUCCESS'
            ]);

            Logged::create([
                'user_id' => $user->id,
                'for' => 'Wallet Top-up',
                'message' => 'Wallet top-up successful',
                'stack_trace' => json_encode($payload),
                't_reference' => $reference,
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'SUCCESS',
            ]);

            // Get updated balance
            $newBalance = $user->balance()->first()->balance;

        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'Wallet Top-up',
                'message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString() . "\n\nPayload: " . json_encode($payload, JSON_PRETTY_PRINT),
                't_reference' => $reference,
                'from' => 'PAYSTACK_WEBHOOK',
                'type' => 'FAILED',
            ]);
            return response()->json(['status' => 'failed'], 500);
        }

        // WhatsApp Alert
        try {
            $this->sendCreditAlertWhatsapp($user, $amount, $reference, $newBalance, $sender_name, $sender_bank);
        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'WhatsApp Credit Alert',
                'message' => 'WhatsApp credit alert failed: ' . $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => $reference,
                'from' => 'WHATSAPP_ALERT',
                'type' => 'FAILED',
            ]);
        }

        // Email Alert
        try {
            Mail::to($user->email)->send(new CreditAlertMail($user, $amount, $transaction));
        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'Email Credit Alert',
                'message' => 'Email credit alert failed: ' . $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => $reference,
                'from' => 'EMAIL_ALERT',
                'type' => 'FAILED',
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    private function sendCreditAlertWhatsapp($user, $amount, $reference, $newBalance, $sender_name, $sender_bank)
    {
        if (!$user || !$user->mobile) {
            Logged::create([
                'user_id' => $user->id ?? null,
                'for' => 'WhatsApp Credit Alert',
                'message' => 'Cannot send WhatsApp: Invalid user or missing mobile number',
                'stack_trace' => json_encode(['user' => $user]),
                't_reference' => $reference,
                'from' => 'WHATSAPP_ALERT',
                'type' => 'FAILED',
            ]);
            return false;
        }

        $sender_name = htmlspecialchars($sender_name, ENT_QUOTES, 'UTF-8');
        $sender_bank = htmlspecialchars($sender_bank, ENT_QUOTES, 'UTF-8');

        $msg = 
            "💰 *CREDIT ALERT*\n\n" .
            "Your A-Pay wallet has been funded.\n\n" .
            "📤 *From:* {$sender_name} | {$sender_bank}\n" .
            "💵 *Amount:* ₦" . number_format($amount, 2) . "\n" .
            "🔖 *Ref:* {$reference}\n" .
            "💼 *New Balance:* ₦" . number_format($newBalance, 2) . "\n\n" .
            "Thank you for using A-Pay! 💚";
        
        try {
            $this->sendMessage($user->mobile, $msg);
            
            Logged::create([
                'user_id' => $user->id,
                'for' => 'WhatsApp Credit Alert',
                'message' => 'Credit alert WhatsApp sent successfully',
                'stack_trace' => json_encode(['mobile' => $user->mobile, 'amount' => $amount]),
                't_reference' => $reference,
                'from' => 'WHATSAPP_ALERT',
                'type' => 'SUCCESS',
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'WhatsApp Credit Alert',
                'message' => 'Failed to send credit alert WhatsApp: ' . $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => $reference,
                'from' => 'WHATSAPP_ALERT',
                'type' => 'FAILED',
            ]);
            
            return false;
        }
    }

    private function sendCreditAlertSMS($user, $amount, $reference, $newBalance, $sender_name, $sender_bank)
    {
        if (!$user || !$user->mobile) {
            Logged::create([
                'user_id' => $user->id ?? null,
                'for' => 'SMS Credit Alert',
                'message' => 'Cannot send SMS: Invalid user or missing mobile number',
                'stack_trace' => json_encode(['user' => $user]),
                't_reference' => $reference,
                'from' => 'SMS_ALERT',
                'type' => 'FAILED',
            ]);
            return false;
        }

        $sender_name = htmlspecialchars($sender_name, ENT_QUOTES, 'UTF-8');
        $sender_bank = htmlspecialchars($sender_bank, ENT_QUOTES, 'UTF-8');

        $msg = 
            "💰 *CREDIT ALERT*\n\n" .
            "Your A-Pay wallet has been funded.\n\n" .
            "📤 *From:* {$sender_name} | {$sender_bank}\n" .
            "💵 *Amount:* ₦" . number_format($amount, 2) . "\n" .
            "🔖 *Ref:* {$reference}\n" .
            "💼 *New Balance:* ₦" . number_format($newBalance, 2) . "\n\n" .
            "Thank you for using A-Pay! 💚";
        
        try {
            $mobile = $this->formatPhoneNumber($user->mobile);
            $this->smsService->sendSms($mobile, $msg);
            
            Logged::create([
                'user_id' => $user->id,
                'for' => 'SMS Credit Alert',
                'message' => 'Credit alert SMS sent successfully',
                'stack_trace' => json_encode(['mobile' => $mobile, 'amount' => $amount]),
                't_reference' => $reference,
                'from' => 'SMS_ALERT',
                'type' => 'SUCCESS',
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $user->id,
                'for' => 'SMS Credit Alert',
                'message' => 'Failed to send credit alert SMS: ' . $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => $reference,
                'from' => 'SMS_ALERT',
                'type' => 'FAILED',
            ]);
            
            return false;
        }
    }
    
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (substr($phone, 0, 1) !== '+') {
            $phone = '+234' . ltrim($phone, '0');
        }

        return $phone;
    }

    private function sendMessage($to, $body)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = 'whatsapp:' . env('TWILIO_W_NUMBER');

        if (!$sid || !$token || !$from) {
            Logged::create([
                'user_id' => null,
                'for' => 'TWILIO_CONFIG',
                'message' => 'Missing Twilio credentials',
                'stack_trace' => json_encode([
                    'sid' => $sid ? 'present' : 'missing',
                    'token' => $token ? 'present' : 'missing',
                    'from' => $from,
                ]),
                'from' => 'TWILIO_WHATSAPP',
                'type' => 'FAILED',
            ]);
            return;
        }

        try {
            $client = new Client($sid, $token);
            $message = $client->messages->create("whatsapp:$to", [
                'from' => $from,
                'body' => $body,
            ]);

            // Record outgoing WhatsApp message
            WhatsappMessage::create([
                'phone_number' => $to,
                'direction' => 'outgoing',
                'message_body' => $body,
                'message_sid' => $message->sid,
                'status' => $message->status,
                'metadata' => [
                    'from' => $from,
                    'sent_at' => now()->toIso8601String(),
                    'has_media' => false
                ]
            ]);

        } catch (\Exception $e) {
            Logged::create([
                'user_id' => null,
                'for' => 'TWILIO_SEND',
                'message' => 'Twilio WhatsApp send failed: ' . $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'from' => 'TWILIO_WHATSAPP',
                'type' => 'FAILED',
            ]);
        }
    }
}