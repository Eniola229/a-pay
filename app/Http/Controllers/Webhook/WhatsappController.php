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

            $sessionData = json_decode($session->data ?? '{}', true) ?? [];

            // Try to extract phone, amount, network from message
            preg_match('/(0\d{10})/', $message, $phoneMatch); 
            
            // Extract amount - look for numbers that are NOT part of phone number (2-6 digits, preferably with space or "of")
            preg_match('/(?:of\s+)?(\d{2,6})(?:\s|$|to)/', $message, $amountMatch);
            
            preg_match('/\b(mtn|glo|airtel|9mobile)\b/i', $message, $networkMatch); 

            // Merge with session data if exists
            $phone = $phoneMatch[1] ?? ($sessionData['phone'] ?? null);
            $amount = isset($amountMatch[1]) && (int)$amountMatch[1] >= 10 ? (float)$amountMatch[1] : ($sessionData['amount'] ?? null);
            $network = isset($networkMatch[1]) ? strtolower($networkMatch[1]) : ($sessionData['network'] ?? null);

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
                return "📱 To buy airtime, send in any of these formats:\n\n*airtime 500 09079916807*\nor\n*send airtime of 500 to 09079916807*\nor\n*airtime AIRTEL 500 09079916807*\n\nChoose any format! 😊";
            }

            // Update or create session
            if (!$session) {
                $session = new WhatsappSession();
                $session->id = Str::uuid();
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
                return "📱 To buy airtime, send:\n\n*airtime 500 09079916807*\nor\n*send airtime of 500 to 09079916807*\n\nEnjoy! 😊";
            }

            if ($phone && !$network && !$amount) {
                return "🎯 You want to buy airtime for *{$phone}*.\n\n💡 Please tell me the *amount*.\n\nExample: *airtime 500 {$phone}*";
            }

            if ($phone && $network && !$amount) {
                return "🎯 You want to buy *" . strtoupper($network) . "* airtime for *{$phone}*.\n\n💰 How much? Reply with:\n\n*airtime " . strtoupper($network) . " 500 {$phone}*\n\nor just: *500* (we'll remember your number 😊)";
            }

            if ($phone && $amount && !$network) {
                return "💰 You want to buy *₦" . number_format($amount) . "* airtime for *{$phone}*.\n\n📶 Which network?\n\nExample: *airtime MTN " . $amount . " {$phone}*";
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
                    
                    // Check if we have all info now
                    if ($sessionData['network']) {
                        return $this->processAirtimePurchase($user, $sessionData['network'], $amount, $phone);
                    }
                    
                    return "💰 Got it! *₦" . number_format($amount) . "* for *{$phone}*.\n\n📶 Which network? (MTN, GLO, Airtel, 9mobile)";
                } else {
                    return "💡 I see you want *₦" . number_format($amount) . "* airtime.\n\n📱 What's the phone number? (e.g., 09079916807)";
                }
            }

            return "⚠️ Please provide correct details.\n\nExample: *airtime 500 09079916807*";
        }
        // 5️⃣ Data
        // Check if user wants to see data plans for a specific network
        if (preg_match('/\b(mtn|airtel|glo|9mobile)\b/i', $message, $networkMatch)) {
            $requestedNetwork = strtolower($networkMatch[1]);
            
            // Only show plans if they don't have a phone number (just want to browse)
            if (!preg_match('/(0\d{10})/', $message)) {
                // Fetch data plans from API
                $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                $allPlans = $response->json()['data'] ?? [];
                $networkPlans = collect($allPlans)->where('service_id', $requestedNetwork)->values();

                if ($networkPlans->isEmpty()) {
                    return "⚠️ No data plans found for *" . strtoupper($requestedNetwork) . "*.";
                }

                $planListMsg = "💾 Available *" . strtoupper($requestedNetwork) . "* data plans:\n\n";
                foreach ($networkPlans as $p) {
                    $planListMsg .= "- " . $p['data_plan'] . " (₦" . $p['price'] . ")\n";
                }
                $planListMsg .= "\n\n✨ Which plan catches your eye? 👀\n\n📝 Just reply with your choice in this format:\n\n*data 09079916807 1GB*\n\nFor example:\n*data 09079916807 100MB*\n\nOr:\n*data 09079916807 5GB*";
                return $planListMsg;
            }
        }
        if (preg_match('/\bdata\b/i', $message)) {

            // Handle cancel command
            if (preg_match('/\bcancel\b/i', $message)) {
                return "❌ Cancelled. Type 'menu' to see other options.";
            }

            // Extract phone and plan from message
            preg_match('/(0\d{10})/', $message, $phoneMatch);
            preg_match('/\b(\d+(?:GB|MB|gb|mb))\b/', $message, $planMatch);

            $phone = $phoneMatch[1] ?? null;
            $plan = $planMatch[1] ?? null;

            // === CASE 1: User typed "data" but NO number ===
            if (!$phone) {
                return "🎉 Oh, you want to buy data? Great choice!\n\n📱 Send your phone number in this format:\n\n*data 09079916807*\n\nMake sure it's your correct phone number so we can send the data to the right place! 😊";
            }

            // === CASE 2: User has phone but NO plan - Show available plans ===
            if ($phone && !$plan) {
                // Auto-detect network from phone prefix
                $prefix = substr($phone, 0, 4);
                $networkPrefixes = [
                    'mtn' => ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
                    'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
                    'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
                    '9mobile' => ['0809','0817','0818','0909','0908']
                ];

                $network = null;
                foreach ($networkPrefixes as $net => $prefixes) {
                    if (in_array($prefix, $prefixes)) {
                        $network = $net;
                        break;
                    }
                }

                if (!$network) {
                    return "⚠️ Invalid phone number. Please use a valid Nigerian number.";
                }

                // Fetch data plans from API
                $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                $allPlans = $response->json()['data'] ?? [];
                $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->values();

                if ($networkPlans->isEmpty()) {
                    return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
                }

                $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans for {$phone}:\n\n";
                foreach ($networkPlans as $p) {
                    $planListMsg .= "- " . $p['data_plan'] . " (₦" . $p['price'] . ")\n";
                }
                $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n📝 Just reply with your choice in this format:\n\n*data 09079916807 1GB*\n\nFor example:\n*data 09079916807 100MB*\n\nOr:\n*data 09079916807 5GB*";
                return $planListMsg;
            }

            // === CASE 3: User has both phone AND plan - Process purchase ===
            if ($phone && $plan) {
                // Auto-detect network
                $prefix = substr($phone, 0, 4);
                $networkPrefixes = [
                    'mtn' => ['0803','0806','0703','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
                    'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
                    'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912'],
                    '9mobile' => ['0809','0817','0818','0909','0908']
                ];

                $network = null;
                foreach ($networkPrefixes as $net => $prefixes) {
                    if (in_array($prefix, $prefixes)) {
                        $network = $net;
                        break;
                    }
                }

                if (!$network) {
                    return "⚠️ Invalid phone number.";
                }

                // Fetch all plans and find the matching one
                $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                $allPlans = $response->json()['data'] ?? [];
                $networkPlans = collect($allPlans)->where('service_id', strtolower($network))->all();

                // Find matching plan
                $selectedPlan = collect($networkPlans)->first(function ($p) use ($plan) {
                    $planData = strtolower(trim($p['data_plan']));
                    $userPlan = strtolower(trim($plan));
                    return strpos($planData, $userPlan) === 0;
                });

                if (!$selectedPlan) {
                    return "⚠️ The plan *{$plan}* is not available for *" . strtoupper($network) . "*.\n\nPlease choose a valid plan and reply:\n*data {$phone} [PLAN]*";
                }

                $planName = $selectedPlan['data_plan'];
                $planPrice = $selectedPlan['price'];
                $variationId = $selectedPlan['variation_id'];

                // Check user balance
                $balance = Balance::where('user_id', $user->id)->first();
                if (!$balance || $balance->balance < $planPrice) {
                    $shortBy = $planPrice - ($balance->balance ?? 0);
                    return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$planPrice}\n🔴 Short by: ₦{$shortBy}\n\nPlease fund your wallet and try again! 💳";
                }

                // Deduct balance
                $balance->decrement('balance', $planPrice);

                // Create transaction record
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'amount' => $planPrice,
                    'beneficiary' => $phone,
                    'description' => "Data purchase: {$planName}",
                    'status' => 'PENDING'
                ]);

                // Create data purchase record
                $dataPurchase = DataPurchase::create([
                    'user_id' => $user->id,
                    'phone_number' => $phone,
                    'data_plan_id' => $variationId,
                    'network_id' => $network,
                    'amount' => $planPrice,
                    'status' => 'PENDING'
                ]);

                // Call API to process purchase
                $apiToken = env('EBILLS_API_TOKEN');
                $requestId = 'REQ_' . strtoupper(Str::random(12));

                try {
                    $response = Http::withToken($apiToken)
                        ->timeout(15)
                        ->post('https://ebills.africa/wp-json/api/v2/data', [
                            'request_id' => $requestId,
                            'phone' => $phone,
                            'service_id' => $network,
                            'variation_id' => $variationId,
                        ]);
                    $responseData = $response->json();
                } catch (\Exception $e) {
                    return "⚠️ Could not reach data provider. Please try again later.";
                }

                // Handle success
                if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
                    $transaction->update(['status' => 'SUCCESS']);
                    $dataPurchase->update(['status' => 'SUCCESS']);

                    $cashback = CashbackService::calculate($planPrice);
                    $balance->increment('balance', $cashback);
                    $transaction->update(['cash_back' => $cashback]);

                    return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$planName}* data has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$planPrice}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your data! 📡🚀";
                } else {
                    Log::error('Data purchase failed', ['response' => $responseData]);
                    $balance->increment('balance', $planPrice);
                    $transaction->update(['status' => 'ERROR']);
                    $dataPurchase->update(['status' => 'FAILED']);

                    return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$planPrice} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
                }
            }

            return "⚠️ Please follow the format:\n*data 09079916807*";
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
            return "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . ($balance->balance ?? 0) . "\n💸 Plan cost: ₦{$amount}\n\nPlease fund your wallet and try again! 💳";
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

            return "
           🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$amount}* airtime has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$amount}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your data! 📡🚀";
        } else {
            $balance->balance += $amount;
            $balance->save();
            $transaction->update(['status' => 'ERROR']);
            $airtime->update(['status' => 'FAILED']);

            return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$amount} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
        }
    }

}
