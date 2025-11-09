<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\Models\User;
use App\Models\Balance;
use Illuminate\Support\Facades\Hash;
use App\Mail\AirtimePurchaseMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Client\RequestException;
use App\Services\CashbackService;
use App\Models\Errors;
use App\Models\Transaction;
use App\Models\AirtimePurchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Mail\DataPurchaseMail;
use Illuminate\Support\Str;
use App\Models\DataPurchase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use App\Models\Borrow;
use App\Models\CreditLimit;
use App\Models\WhatsappSession;


class WhatsappController extends Controller
{
    public function handle(Request $request)
    {
        $from = str_replace('whatsapp:', '', $request->input('From'));
        $message = strtolower(trim($request->input('Body')));

        // Check if user exists in your users table
        $user = User::where('mobile', $from)->first();
        if (!$user) {
            // Use a session keyed by 'register' context only
            $session = WhatsappSession::firstOrCreate(
                ['context' => 'register'],
                ['data' => json_encode([])]
            );

            $sessionData = json_decode($session->data ?? '{}', true);

            // Try to parse name and email from message
            // Accept message like "John Doe john@gmail.com"
            if (preg_match('/([a-zA-Z ]+)\s+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,})/i', $message, $matches)) {
                $name = trim($matches[1]);
                $email = trim($matches[2]);
            } else {
                $name = $sessionData['name'] ?? null;
                $email = $sessionData['email'] ?? null;
            }

            // Update session data
            $session->data = json_encode([
                'name' => $name,
                'email' => $email,
                'phone' => $from
            ]);
            $session->save();

            // Ask for name/email if missing
            if (!$name || !$email) {
                return $this->sendMessage(
                    $from,
                    "👋 Welcome to *A-Pay!* \nPlease reply with your *Name* and *Email* in this format:\nJohn Doe john@gmail.com"
                );
            }

            // Create user
            $accountNumber = str_replace('+234', '', $from);
            $user = User::create([
                'name' => $name,
                'mobile' => $from,
                'email' => $email,
                'password' => '', // empty password
                'account_number' => $accountNumber,
            ]);

            Balance::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            // Link session to actual user_id now
            $session->user_id = $user->id;
            $session->save();

            return $this->sendMessage(
                $from,
                "🎉 Congratulations *{$name}*! You have been registered successfully with your WhatsApp number as your mobile. You can now use A-Pay services."
            );
        }
        // detect intent
        $response = $this->processCommand($user, $message);

        // reply
        $this->sendMessage($from, $response);
    }

    private function processCommand($user, $message)
    {
        // 1️⃣ Greetings
        if (in_array($message, [
            'hi', 'hello', 'hey', 'hey there', 'menu', 'help', 'Hi', 'Hello', 'Help',
            'good morning', 'good afternoon', 'good evening', 'good night', 'morning',
            'afternoon', 'evening', 'night', 'hola', 'yo', 'hiya', 'greetings', 'sup', 'what\'s up', 'apay', 'A-Pay'
        ])) {
            return $this->mainMenu($user);
        }

        // 2️⃣ Funding wallet
        if (preg_match('/fund|deposit/i', $message)) {
            $amount = $this->extractAmount($message);

            if (!$amount) {
                return "💰 Please enter the amount you want to fund.\nExample: *fund 2000*";
            }

            try {
                $userEmail = $user->email;
                $amountKobo = $amount * 100; 
                $callbackUrl = url('/api/whatsapp/topup/callback'); 

                $client = new \GuzzleHttp\Client();
                $response = $client->post('https://api.paystack.co/transaction/initialize', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'email'        => $userEmail,
                        'amount'       => $amountKobo,
                        'callback_url' => $callbackUrl,
                    ],
                ]);

                $body = json_decode($response->getBody(), true);

                if ($body['status'] && isset($body['data']['authorization_url'])) {
                    // Record transaction in database
                    $transaction = Transaction::create([
                        'user_id'     => $user->id,
                        'beneficiary' => $user->name . ' | ' . $user->mobile,
                        'amount'      => $amount,
                        'description' => 'Wallet Top-up',
                        'status'      => 'PENDING',
                        'reference' => $body['data']['reference'],
                    ]);

                    $payUrl = $body['data']['authorization_url'];
                    \Log::info('Paystack reference received', ['reference' => $body['data']['reference']]);

                    // Store mobile number in cache for WhatsApp callback notification
                    Cache::put('whatsapp_topup_' . $transaction->id, $user->mobile, now()->addMinutes(30));

                    return "💰 To fund your wallet with ₦{$amount}, click this secure link:\n{$payUrl}";
                }

                return "❌ Unable to initialize payment. Please try again.";

            } catch (\Exception $e) {
                return "❌ Payment initialization failed: " . $e->getMessage();
            }
        }

        // 3️⃣ View wallet balance
        if (preg_match('/balance|wallet/i', $message)) {
            $balance = Balance::where('user_id', $user->id)->first();

            if (!$balance) {
                return "❌ You don't have a balance yet.\nPlease fund your wallet first.";
            }

            $amount = number_format($balance->balance ?? 0, 2);
            return "💵 Your current wallet balance is: ₦{$amount}";
        }

        // 4️⃣ Airtime
        // Airtime handling
        if (preg_match('/(airtime|recharge|top\s?up|buy\s?airtime)/i', $message)) {

            $session = WhatsappSession::where('user_id', $user->id)
                        ->where('context', 'airtime')
                        ->latest()
                        ->first();

            $sessionData = json_decode($session->data ?? '{}', true);

            // Try to extract phone, amount, network from message
            preg_match('/(0\d{10})/', $message, $phoneMatch); 
            preg_match('/\b(\d{2,5})\b/', $message, $amountMatch); 
            preg_match('/\b(mtn|glo|airtel|9mobile)\b/i', $message, $networkMatch); 

            // Merge with session data if exists
            $phone = $phoneMatch[1] ?? $sessionData['phone'] ?? null;
            $amount = isset($amountMatch[1]) && (int)$amountMatch[1] >= 10 ? (float)$amountMatch[1] : $sessionData['amount'] ?? null;
            $network = isset($networkMatch[1]) ? strtolower($networkMatch[1]) : $sessionData['network'] ?? null;

            // Auto-detect network if missing
            if (!$network && $phone) {
                $prefix = substr($phone, 0, 4);
                $networkPrefixes = [
                    'mtn' => ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
                    'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
                    'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
                    '9mobile' => ['0809','0817','0818','0909','0908']
                ];
                foreach ($networkPrefixes as $net => $prefixes) {
                    if (in_array($prefix, $prefixes)) {
                        $network = $net;
                        break;
                    }
                }
            }

            // If there is no session and no phone/amount/network, show help
            if (!$session && !$phone && !$amount && !$network) {
                return "📱 To buy airtime, send:\n*airtime network amount phone*\nExample: airtime MTN 500 08012345678";
            }

            // Update or create session
            if (!$session) {
                $session = new WhatsappSession();
                $session->id = \Str::uuid();
                $session->user_id = $user->id;
                $session->context = 'airtime';
            }

            $session->data = json_encode([
                'phone' => $phone,
                'network' => $network,
                'amount' => $amount
            ]);
            $session->save();

            // Respond based on missing info
            if (!$phone && !$network && !$amount) {
                return "📱 To buy airtime, send:\n*airtime network amount phone*\nExample: airtime MTN 500 08012345678";
            }

            if ($phone && !$network && !$amount) {
                return "💡 You want to buy airtime for *{$phone}*.\nPlease tell me the *network* and *amount*.\nExample: airtime MTN 500 {$phone}";
            }

            if ($phone && $network && !$amount) {
                return "💰 You want to buy *" . strtoupper($network) . "* airtime for *{$phone}*.\nHow much do you want to recharge?\nJust reply with the amount (number only).";
            }

            if ($phone && $amount && !$network) {
                return "📶 You want to buy *₦{$amount}* airtime for *{$phone}*.\nPlease tell me the *network* (MTN, GLO, Airtel, 9mobile).";
            }

            if ($phone && $network && $amount) {
                $session->delete(); // clear session after purchase
                return $this->processAirtimePurchase($user, $network, $amount, $phone);
            }

            // If the user typed only amount but no phone yet and there is an existing session
            if ($amount && !$phone && $sessionData) {
                $phone = $sessionData['phone'] ?? null;
                if ($phone) {
                    $session->data = json_encode([
                        'phone' => $phone,
                        'network' => $sessionData['network'] ?? null,
                        'amount' => $amount
                    ]);
                    $session->save();
                    return "💰 You entered *₦{$amount}*.\nPlease tell me the phone number you want to recharge.";
                } else {
                    return "💡 I see you entered *₦{$amount}*.\nPlease tell me the phone number you want to recharge.";
                }
            }

            return "⚠️ Please provide correct details.\nExample: airtime MTN 500 08012345678";
        }


        // 5️⃣ Data
        if (preg_match('/\bdata\b/i', $message)) {

            $session = WhatsappSession::where('user_id', $user->id)
                        ->where('context', 'data')
                        ->latest()
                        ->first();

            $sessionData = json_decode($session->data ?? '{}', true);

            preg_match('/(0\d{10})/', $message, $phoneMatch);
            preg_match('/\b(\d+(?:GB|MB|gb|mb))\b/', $message, $planMatch);

            $phone = $phoneMatch[1] ?? $sessionData['phone'] ?? null;
            $plan = $planMatch[1] ?? $sessionData['plan'] ?? null;
            $network = $sessionData['network'] ?? null;

            // Auto-detect network
            if ($phone && !$network) {
                $prefix = substr($phone, 0, 4);
                $networkPrefixes = [
                    'mtn' => ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
                    'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
                    'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
                    '9mobile' => ['0809','0817','0818','0909','0908']
                ];
                foreach ($networkPrefixes as $net => $prefixes) {
                    if (in_array($prefix, $prefixes)) {
                        $network = $net;
                        break;
                    }
                }
            }

            // Update/create session
            if (!$session) {
                $session = new WhatsappSession();
                $session->id = Str::uuid();
                $session->user_id = $user->id;
                $session->context = 'data';
            }

            $session->data = json_encode([
                'phone' => $phone,
                'network' => $network,
                'plan' => $plan
            ]);
            $session->save();

            // Step 1: Ask for phone
            if (!$phone) {
                return "💾 To buy data, send your phone number.\nExample: 08031234567";
            }

            // Step 2: Ask user to select plan
            if ($phone && $network && !$plan) {
                $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                $allPlans = $response->json()['data'] ?? [];
                $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->all();

                if (empty($networkPlans)) {
                    return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
                }

                $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans for {$phone}:\n";
                foreach ($networkPlans as $p) {
                    $planListMsg .= "- " . $p['data_plan'] . " (₦" . $p['price'] . ")\n";
                }
                $planListMsg .= "\nReply with the plan you want to buy (e.g., 1GB).";
                return $planListMsg;
            }

            // Step 3: Process purchase if phone, network, and plan exist
            if ($phone && $network && $plan) {
                $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                $allPlans = $response->json()['data'] ?? [];
                $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->all();

                $selectedPlan = null;
                foreach ($networkPlans as $p) {
                    if (strtolower($p['data_plan']) === strtolower($plan)) {
                        $selectedPlan = $p;
                        break;
                    }
                }

                if (!$selectedPlan) {
                    return "⚠️ The plan *{$plan}* is not available for *" . strtoupper($network) . "*.\nPlease choose a valid plan.";
                }

                $planName = $selectedPlan['data_plan'];
                $planPrice = $selectedPlan['price'];
                $variationId = $selectedPlan['variation_id'];

                $balance = Balance::where('user_id', $user->id)->first();
                if ($balance->balance < $planPrice) {
                    return "⚠️ Insufficient balance. Your wallet has ₦{$balance->balance}, but this plan costs ₦{$planPrice}.";
                }

                $balance->balance -= $planPrice;
                $balance->save();

                // Create transaction & data purchase
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $planPrice,
                    'beneficiary' => $phone,
                    'description' => "Data purchase: {$planName}",
                    'status' => 'PENDING'
                ]);

                $dataPurchase = DataPurchase::create([
                    'user_id' => $user->id,
                    'phone_number' => $phone,
                    'data_plan_id' => $variationId,
                    'network_id' => $network,
                    'amount' => $planPrice,
                    'status' => 'PENDING'
                ]);

                // Call Ebills API
                $apiToken = env('EBILLS_API_TOKEN');
                $requestId = 'REQ_' . strtoupper(Str::random(12));
                $payload = [
                    'request_id' => $requestId,
                    'phone' => $phone,
                    'service_id' => $network,
                    'variation_id' => $variationId,
                ];

                try {
                    $response = Http::withToken($apiToken)->timeout(15)->post('https://ebills.africa/wp-json/api/v2/data', $payload);
                    $responseData = $response->json();
                } catch (\Exception $e) {
                    return "⚠️ Could not reach data provider. Please try again later.";
                }

                if ($response->successful() && isset($responseData['code']) && $responseData['code'] === 'success') {
                    $transaction->update(['status' => 'SUCCESS']);
                    $dataPurchase->update(['status' => 'SUCCESS']);

                    $cashback = CashbackService::calculate($planPrice);
                    $balance->balance += $cashback;
                    $balance->save();
                    $transaction->cash_back += $cashback;
                    $transaction->save();

                    $session->delete();

                    return "✅ Success! You purchased *{$planName}* for *{$phone}* on *" . strtoupper($network) . "* for ₦{$planPrice}.\n💰 Cashback earned: ₦{$cashback}";
                } else {
                    Log::error('Data purchase failed', ['response' => $responseData]);
                    $balance->increment('balance', $planPrice);
                    $transaction->update(['status' => 'ERROR']);
                    $dataPurchase->update(['status' => 'FAILED']);
                    $session->delete();

                    return "⚠️ Data purchase failed. Please try again later.";
                } 
            }

            return "⚠️ Please provide correct details.\nExample: data 08031234567";
        }

        // 6️⃣ Electricity
        if (preg_match('/electric|bill|meter/i', $message)) {
            return "⚡ To pay electricity bill, send:\n\n*electric meter_no amount*\nExample: electric 1234567890 5000";
        }

        // 7️⃣ Betting
        if (preg_match('/bet/i', $message)) {
            return "🎯 To fund betting account, send:\n\n*bet platform amount*\nExample: bet sportybet 1000";
        }

        // 8️⃣ Transactions
        if (preg_match('/transactions|history/i', $message)) {
            $latest = $user->transactions()->latest()->take(5)->get();
            if ($latest->isEmpty()) {
                return "🧾 No recent transactions found.";
            }

            $msg = "🧾 *Recent Transactions:*\n\n";
            foreach ($latest as $t) {
                $msg .= "• Beneficiary: {$t->beneficiary}\n";
                $msg .= "  Amount: ₦{$t->amount}\n";
                $msg .= "  Cash Back: ₦{$t->cash_back}\n";
                $msg .= "  Charges: ₦{$t->charges}\n";
                $msg .= "  Description: {$t->description}\n";
                $msg .= "  Status: {$t->status}\n";
                $msg .= "  Reference: {$t->reference}\n\n";
            }

            return trim($msg);
        }

        // fallback
        return "❓ Sorry, I didn’t understand that.\n\nType *menu* to see available options.";
    }


    private function mainMenu($user)
    {
        return "👋 Hi *{$user->name}*, welcome back to *A-Pay!*\n\n" .
               "Please reply with a command:\n\n" .
               "1️⃣ airtime — Buy Airtime\n" .
               "2️⃣ data — Buy Data\n" .
               "3️⃣ electric — Pay Electricity Bill\n" .
               "4️⃣ bet — Fund Betting Account\n" .
               "5️⃣ fund — Fund Wallet\n" .
               "6️⃣ balance — View Wallet Balance\n" .
               "7️⃣ transactions — View Recent Transactions\n\n" .
               "Example: *fund 2000* or *airtime MTN 500 08012345678*";
    }


    private function extractAmount($text)
    {
        preg_match('/\d+/', $text, $match);
        return $match[0] ?? null;
    }

    private function sendMessage($to, $body)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = 'whatsapp:' . env('TWILIO_W_NUMBER');

        if (!$sid || !$token || !$from) {
            \Log::error('Missing Twilio credentials', [
                'sid' => $sid,
                'token' => $token,
                'from' => $from,
            ]);
            return;
        }
        $client = new Client($sid, $token);
        $client->messages->create("whatsapp:$to", [
            'from' => $from,
            'body' => $body,
        ]);
    }

    public function whatsappCallback(Request $request)
    {
        $reference = $request->query('reference');
        \Log::info('Paystack callback received:', $request->all());


        if (!$reference) {
            return response()->json(['error' => 'No payment reference provided.'], 400);
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.paystack.co/transaction/verify/{$reference}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        $transaction = Transaction::where('reference', $reference)->latest()->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found.'], 404);
        }

        // Get WhatsApp number stored in cache
        $mobile = \Cache::pull('whatsapp_topup_' . $transaction->id);

        if ($body['status'] && $body['data']['status'] === 'success') {

            $amount = $body['data']['amount'] / 100; // Convert kobo to Naira
            $user = $transaction->user;

            // Update transaction
            $transaction->status = "SUCCESS";
            $transaction->save();

            // Get or create balance
            $balance = Balance::firstOrCreate(
                ['user_id' => $user->id],
                ['id' => \Str::uuid(), 'balance' => 0, 'pin' => '']
            );

            $originalTopup = $amount;
            $totalDeducted = 0;

            // Repay unpaid loans
            $unpaidLoans = Borrow::where('user_id', $user->id)
                ->where('repayment_status', '!=', 'PAID')
                ->where('status', 'approved')
                ->orderBy('created_at', 'asc')
                ->get();

            $balanceOwe = Balance::where('user_id', $user->id)->first();

            foreach ($unpaidLoans as $loan) {
                if ($amount <= 0) break;

                $loanBalance = $loan->amount;

                if ($amount >= $loanBalance) {
                    $loan->repayment_status = 'PAID';
                    $amount -= $loanBalance;
                    $totalDeducted += $loanBalance;
                    if ($balanceOwe) $balanceOwe->owe -= $loanBalance;
                } else {
                    $loan->repayment_status = 'NOT PAID FULL';
                    $totalDeducted += $amount;
                    if ($balanceOwe) $balanceOwe->owe -= $amount;
                    $amount = 0;
                }

                $loan->save();
                if ($balanceOwe) $balanceOwe->save();
            }

            // Update balance with remaining amount
            $balance->balance += $amount;
            $balance->save();

            // Update credit limit
            $creditLimit = CreditLimit::firstOrNew(['user_id' => $user->id]);
            $creditLimit->id = $creditLimit->id ?? \Str::uuid();
            $creditLimit->limit_amount += $totalDeducted;
            $creditLimit->save();

            // Notify user via WhatsApp
            $message = "✅ Top-up successful! ₦" . number_format($originalTopup, 2) . " added to your wallet.";
            if ($totalDeducted > 0) {
                $message .= " ₦" . number_format($totalDeducted, 2) . " was deducted to repay your loan.";
            }

            if ($mobile) {
                $this->sendMessage($mobile, $message);
            }

            return response()->json(['success' => $message]);

        } else {
            // Payment failed
            $transaction->status = "ERROR";
            $transaction->save();

            if ($mobile) {
                $this->sendMessage($mobile, "❌ Top-up failed. Please try again.");
            }

            return response()->json(['error' => 'Payment failed.'], 400);
        }
    }

    private function processAirtimePurchase($user, $network, $amount, $phone)
    {
        $balance = Balance::where('user_id', $user->id)->first();

        if (!$balance || $balance->balance < $amount) {
            return "💸 Insufficient balance. Please fund your wallet to continue.";
        }

        $balance->balance -= $amount;
        $balance->save();

        $airtime = AirtimePurchase::create([
            'user_id' => $user->id,
            'phone_number' => $phone,
            'amount' => $amount,
            'network_id' => $network,
            'status' => 'PENDING'
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'beneficiary' => $phone,
            'description' => strtoupper($network) . " airtime purchase for " . $phone,
            'status' => 'PENDING'
        ]);

        $apiUrl = 'https://ebills.africa/wp-json/api/v2/airtime';
        $headers = [
            'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
            'Content-Type' => 'application/json'
        ];

        $data = [
            'request_id' => 'req_' . uniqid(),
            'phone' => $phone,
            'service_id' => $network,
            'amount' => $amount
        ];

        try {
            $response = Http::withHeaders($headers)->post($apiUrl, $data);
        } catch (\Exception $e) {
            $balance->balance += $amount;
            $balance->save();
            return "⚠️ Network error. Please try again later.";
        }

        if ($response->successful() && ($response->json()['code'] ?? '') === 'success') {
            $transaction->update(['status' => 'SUCCESS']);
            $airtime->update(['status' => 'SUCCESS']);

            if (class_exists(\App\Services\CashbackService::class)) {
                $cashback = app(\App\Services\CashbackService::class)->calculate($amount);
                $balance->balance += $cashback;
                $balance->save();
                $transaction->cash_back = $cashback;
                $transaction->save();
            }

            return "✅ Airtime of ₦{$amount} to {$phone} ({$network}) was successful!";
        } else {
            $balance->balance += $amount;
            $balance->save();
            $transaction->update(['status' => 'ERROR']);
            $airtime->update(['status' => 'FAILED']);

            return "❌ Airtime purchase failed. Please try again later.";
        }
    }

}
