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
use App\Mail\ElectricityPaymentReceipt;
use App\Models\ElectricityPurchase;
use Illuminate\Support\Facades\Log;



class WhatsappController extends Controller
{
    public function handle(Request $request)
    {
        $from = str_replace('whatsapp:', '', $request->input('From'));
        $message = strtolower(trim($request->input('Body')));

        // Check if user exists in your users table
        $user = User::where('mobile', $from)->first();
        if (!$user) {

            $session = WhatsappSession::firstOrCreate(
                ['context' => 'register'],
                ['data' => json_encode([])]
            );

            $sessionData = json_decode($session->data ?? '{}', true);

            // Parse name + email
            if (preg_match('/([a-zA-Z ]+)\s+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,})/i', $message, $matches)) {
                $name  = trim($matches[1]);
                $email = trim($matches[2]);
            } else {
                $name  = $sessionData['name']  ?? null;
                $email = $sessionData['email'] ?? null;
            }

            // Update session cache
            $session->data = json_encode([
                'name'  => $name,
                'email' => $email,
                'phone' => $from
            ]);
            $session->save();

            // Ask for missing details
            if (!$name || !$email) {
                return $this->sendMessage(
                    $from,
                    "👋 Welcome to *A-Pay!* \n\nTo create an account, reply with your _Name_ and _Email_ like this:\n\n*John Doe john@gmail.com*"
                );
            }

            // Check if email already exists locally
            if (User::where('email', $email)->exists()) {
                return $this->sendMessage($from,
                    "⚠️ The email *{$email}* already exists.\n\nUse the same phone number you registered with."
                );
            }

            // PREPARE NAME DATA
            $parts = explode(' ', trim($name), 2);
            $firstName = $parts[0];
            $lastName  = $parts[1] ?? $parts[0];

            // Wrap entire Paystack + User creation in a DB transaction
            DB::beginTransaction();
            try {
                // CHECK IF PAYSTACK CUSTOMER ALREADY EXISTS
                $customerLookup = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
                ])->get("https://api.paystack.co/customer/{$email}");

                $lookupData = $customerLookup->json();

                if (isset($lookupData['data']['customer_code'])) {
                    // Existing Paystack customer
                    $customerCode = $lookupData['data']['customer_code'];

                    // Update customer with new name + phone
                    $updateCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
                    ])->put("https://api.paystack.co/customer/{$customerCode}", [
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'phone'      => $from,
                    ]);

                    $updateData = $updateCustomer->json();
                    if (!($updateData['status'] ?? false)) {
                        Log::error('Paystack update customer failed', $updateData);
                        throw new \Exception('Failed to update Paystack customer.');
                    }

                } else {
                    // CREATE PAYSTACK CUSTOMER
                    $createCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
                    ])->post('https://api.paystack.co/customer', [
                        'email'      => $email,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'phone'      => $from,
                    ]);

                    $customerData = $createCustomer->json();

                    if (!($customerData['status'] ?? false) || !isset($customerData['data']['customer_code'])) {
                        Log::error('Paystack customer creation failed', $customerData);
                        throw new \Exception('Failed to create Paystack customer.');
                    }

                    $customerCode = $customerData['data']['customer_code'];
                }

                // CREATE PAYSTACK DEDICATED VIRTUAL ACCOUNT
                $vaResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
                ])->post('https://api.paystack.co/dedicated_account', [
                    'customer'       => $customerCode,
                    'preferred_bank' => 'wema-bank',
                    'currency'       => 'NGN',
                ]);

                $vaData = $vaResponse->json();

                if (!($vaData['status'] ?? false) || !isset($vaData['data']['account_number'])) {
                    Log::error('Paystack VA creation failed', $vaData);
                    throw new \Exception('Failed to create Paystack Virtual Account.');
                }

                // Extract account info
                $accountNumber = $vaData['data']['account_number'];
                $bankName      = $vaData['data']['bank']['name'] ?? null;
                $accountName   = $vaData['data']['account_name'] ?? null;

                if (!$accountNumber || !$bankName || !$accountName) {
                    Log::error('Paystack VA missing required fields', $vaData);
                    throw new \Exception('Incomplete Virtual Account info.');
                }

                // CREATE USER LOCALLY
                $user = User::create([
                    'name'           => ucwords(strtolower($name)),
                    'mobile'         => $from,
                    'email'          => $email,
                    'password'       => '',
                    'account_number' => $accountNumber,
                ]);

                Balance::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                ]);

                // Link session to user
                $session->user_id = $user->id;
                $session->save();

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Registration failed', ['error' => $e->getMessage()]);
                return $this->sendMessage($from,
                    "❌ Registration failed: " . $e->getMessage() . "\nPlease try again."
                );
            }

            // SEND WELCOME MESSAGE
            $this->sendMessage(
                $from,
                "🎉 *Congratulations {$name}!* 🎉\n\n".
                "Your A-Pay account has been created successfully! 🎊\n\n".
                "You can now buy:\n".
                "💵 Airtime\n📶 Data\n💡 Bills\n⚡ Utilities & more\n\n".
                "Type *menu* to see available services.\n\n".
                "__🔐 For security, enable WhatsApp Lock.__"
            );

            // SEND FUNDING ACCOUNT DETAILS
            return $this->sendMessage(
                $from,
                "💰 *TO FUND YOUR A-PAY WALLET*\n\n".
                "🏦 *Bank:* {$bankName}\n".
                "👤 *Account Name:* {$accountName}\n".
                "🔢 *Account Number:* {$accountNumber}\n\n".
                "Transfer to the account above to top-up instantly.\n\n".
                "__Kindly PIN this message for easy access.__"
            );
        }


        if ($user->is_status === 'BLOCKED') {
            return $this->sendMessage(
                $from,
                "*⚠️ Your A-Pay account has been BLOCKED! 🔒* \n\n Please reach out to Customer Support on WhatsApp 📲 09079916807 to get it restored."
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
            return 
                "💰 *TO FUND YOUR A-PAY WALLET*\n\n" .
                "🏦 *Bank:* Wema Bank\n" .
                "👤 *Account Name:* AFRICICL/" . strtoupper($user->name) . "\n" .
                "🔢 *Account Number:* {$user->account_number}\n\n" .
                "Transfer to the account above to top-up instantly.\n\n" .
                "__Kindly PIN this message to easily access it__";
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
                    return "*🎯 You want to buy airtime?*.\n\n💡 Please tell me the *amount and number*.\n\nExample: *airtime 500 09012345678*";
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
                return "🎉 Oh, you want to buy data? Great choice!\n\n📱 Send your phone number in this format:\n\n*data 09079916807*\n\nMake sure it's your correct phone number so we can send the data plans! 😊";
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
      if (preg_match('/(electric|bill|meter|electricity)/i', $message)) {

        // Handle cancel command
        if (preg_match('/\bcancel\b/i', $message)) {
            return "❌ Cancelled. Type 'menu' to see other options.";
        }

        // Extract meter number and amount from message
        // Meter number is typically 10-11 digits
        preg_match('/(\d{10,11})/', $message, $meterMatch);
        
        // Extract ALL numbers in the message
        preg_match_all('/\d+/', $message, $allNumbers);
        
        // Extract provider/network if mentioned (abuja, eko, ibadan, ikeja, jos, kaduna, kano, portharcourt)
        preg_match('/\b(abuja|eko|ibadan|ikeja|jos|kaduna|kano|portharcourt)\b/i', $message, $providerMatch);

        $meterNumber = $meterMatch[1] ?? null;
        $provider = isset($providerMatch[1]) ? strtolower($providerMatch[1]) : null;
        
        // Find the amount - it's the number that is NOT the meter number and is between 100-999999
        $amount = null;
        if (!empty($allNumbers[0])) {
            foreach ($allNumbers[0] as $num) {
                $numInt = (int)$num;
                // Amount should be between 100 and 999999, and NOT the meter number
                if ($numInt >= 100 && $numInt <= 999999 && $num !== $meterNumber) {
                    $amount = (float)$num;
                    break;
                }
            }
        }

        // Map provider to service_id
        $providerMap = [
            'abuja' => 'abuja-electric',
            'eko' => 'eko-electric',
            'ibadan' => 'ibadan-electric',
            'ikeja' => 'ikeja-electric',
            'jos' => 'jos-electric',
            'kaduna' => 'kaduna-electric',
            'kano' => 'kano-electric',
            'portharcourt' => 'portharcourt-electric'
        ];

        // === CASE 1: User typed "electric" but NO details ===
        if (!$meterNumber && !$amount && !$provider) {
            return "⚡ Oh, you want to pay an electricity bill? Awesome!\n\n📝 Send in this format:\n\n*electric meter_number amount provider*\n\nExample:\n*electric 1234567890 5000 eko*\n\nProviders: abuja, eko, ibadan, ikeja, jos, kaduna, kano, portharcourt\n\nOr just the basics:\n*electric 1234567890 5000*";
        }

        // === CASE 2: Only meter number ===
        if ($meterNumber && !$amount && !$provider) {
            return "🎯 Meter number: *{$meterNumber}*\n\n💰 How much do you want to pay?\n\nExample: *electric {$meterNumber} 5000 eko*";
        }

        // === CASE 3: Meter + Amount but no provider ===
        if ($meterNumber && $amount && !$provider) {
            return "💰 Payment: *₦" . number_format($amount) . "* for meter *{$meterNumber}*\n\n📍 Which electricity provider?\n\n*abuja | eko | ibadan | ikeja | jos | kaduna | kano | portharcourt*\n\nReply: *electric {$meterNumber} {$amount} eko*";
        }

        // === CASE 4: All details provided - Process payment ===
        if ($meterNumber && $amount && $provider) {
            if ($amount < 500) {
                return "⚠️ Minimum amount is ₦500.\n\nYou entered: ₦" . number_format($amount) . "\n\nPlease try again with a higher amount.";
            }

            // Get balance
            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance) {
                return "❌ Account error. Please contact support.";
            }

            // Calculate total (amount + service fee + system fee)
            $serviceFee = 39;
            $systemFee = 60;
            $totalAmount = $amount + $serviceFee + $systemFee;

            // Check balance
            if ($balance->balance < $totalAmount) {
                $shortBy = $totalAmount - $balance->balance;
                return "😔 Insufficient balance.\n\n💰 Your wallet: ₦" . number_format($balance->balance) . "\n💸 Total needed: ₦" . number_format($totalAmount) . " (₦" . number_format($amount) . " + fees)\n🔴 Short by: ₦" . number_format($shortBy) . "\n\nPlease fund your wallet! 💳";
            }

            // Deduct balance
            $balance->decrement('balance', $totalAmount);

            // Create electricity purchase record
            $electricityPurchase = ElectricityPurchase::create([
                'user_id'      => $user->id,
                'meter_number' => $meterNumber,
                'provider_id'  => $providerMap[$provider] ?? $provider,
                'amount'       => $amount,
                'service_fee'  => $serviceFee,
                'total_amount' => $totalAmount,
                'status'       => 'PENDING'
            ]);

            // Create transaction record
            $transaction = Transaction::create([
                'user_id'     => $user->id,
                'amount'      => $totalAmount,
                'beneficiary' => $meterNumber,
                'description' => "Electricity bill payment for meter " . $meterNumber,
                'status'      => 'PENDING'
            ]);

            // Call API to process payment
            $apiToken = env('EBILLS_API_TOKEN');
            $requestId = 'REQ_' . strtoupper(Str::random(12));

            try {
                $response = Http::withToken($apiToken)
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/electricity', [
                        'request_id'   => $requestId,
                        'customer_id'  => $meterNumber,
                        'service_id'   => $providerMap[$provider] ?? $provider,
                        'variation_id' => 'prepaid',
                        'amount'       => $amount,
                    ]);
                $responseData = $response->json();
            } catch (\Exception $e) {
                $balance->increment('balance', $totalAmount);
                $transaction->update(['status' => 'ERROR']);
                $electricityPurchase->update(['status' => 'FAILED']);
                return "⚠️ Could not reach provider. Please try again later. Your balance has been restored.";
            }

            // Handle success
            if ($response->successful() && ($responseData['code'] ?? '') === 'success') {
                $token = $responseData['token'] ?? 'N/A';
                $units = $responseData['units'] ?? 'N/A';

                $transaction->update([
                    'status'      => 'SUCCESS',
                    'description' => "Electricity bill payment for meter {$meterNumber} | Token: {$token} | Units: {$units}"
                ]);
                $electricityPurchase->update(['status' => 'SUCCESS']);

                // Send confirmation email
                try {
                    Mail::to($user->email)->send(new ElectricityPaymentReceipt([
                        'user' => $user,
                        'meterNumber' => $meterNumber,
                        'provider' => $provider,
                        'amount' => $amount,
                        'token' => $token,
                        'units' => $units,
                        'status' => 'SUCCESS'
                    ]));
                } catch (\Exception $e) {
                    Log::error('Email send failed', ['error' => $e->getMessage()]);
                }

                return "🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Electricity bill paid successfully!\n\n📊 Details:\n💡 Meter: *{$meterNumber}*\n🏢 Provider: *" . ucfirst($provider) . "*\n💰 Amount Paid: ₦" . number_format($amount) . "\n⚡ Token: *{$token}*\n📈 Units: *{$units}*\n\n🎁 Check your email for receipt!\n\nEnjoy your power supply! 🔌";
            } else {
                Log::error('Electricity payment failed', ['response' => $responseData]);
                $balance->increment('balance', $totalAmount);
                $transaction->update(['status' => 'ERROR']);
                $electricityPurchase->update(['status' => 'FAILED']);

                $errorMsg = $responseData['message'] ?? 'Payment failed. Please try again.';
                return "❌ Payment failed.\n\n⚠️ " . $errorMsg . "\n\nYour balance of ₦" . number_format($totalAmount) . " has been restored.\n\nPlease try again or contact support. 📞";
            }
        }

        return "⚠️ Invalid format.\n\nExample: *electric 1234567890 5000 eko*";
    }
        // 7️⃣ Support / Customer Care
            if (preg_match('/(support|customer\s*care|help|agent|contact|complain)/i', $message)) {
                return "💚 *A-Pay Support Team*\n\nIf you need assistance, please contact our support via WhatsApp:\n👉 *09079916807*\n\nWe’re available to help you resolve any issue as quickly as possible.\n\nIf you’d like to return to the *main menu*, simply type:\n➡️ *menu*";
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

        // 💬 Thank You / Appreciation
        if (preg_match('/\b(thank you|thanks|thx|sharp)\b/i', $message)) {
            return "💚 You’re welcome! 😊\n\n" .
                   "If you’d like to return to the main menu, just type:\n➡️ *menu*";
        }

        // 💬 Founder / CEO / President Info
        if (preg_match('/who\s+is\s+(the\s+)?(founder|ceo|president)\s+of\s+a-?pay/i', $message)) {
            return "💚 Joshua Adeyemi is the founder and CEO of *A-Pay*, a Nigerian software engineer based in Lagos. He builds solutions that solve real-world problems.\n\n" .
                   "If you’d like to return to the main menu, type:\n➡️ *menu*";
        }

        // 💬 Company Registration Info
        if (preg_match('/a-?pay.*register(ed)?/i', $message)) {
            return "💚 *A-Pay* operates under AfricGEM International Company Limited, a fully registered company in Nigeria under CAC.\n\n" .
                   "Registration Number: 8088462\n\n" .
                   "If you’d like to return to the main menu, type:\n➡️ *menu*";
        }


        // 💬 What is A-Pay / About
        if (preg_match('/what\s+is\s+a-?pay/i', $message)) {
            return "💚 *A-Pay* is a seamless platform that helps you:\n" .
                   "- Buy Airtime\n- Buy Data\n- Pay Electricity Bills\n- Fund your wallet and track transactions easily.\n\n" .
                   "All services are accessible via WhatsApp and our website.\n\n" .
                   "Type *menu* to return to the main menu.";
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
               "4️⃣ fund — Fund Wallet\n" .
               "5️⃣ balance — View Wallet Balance\n" .
               "6️⃣ transactions — View Recent Transactions\n\n" .
               "💬 *Support / Customer Care*\n" .
               "If you need assistance, please contact us on WhatsApp:\n" .
               "👉 *09079916807*\n\n" .
               "We’re always ready to help you with any issue.\n\n" .
               "*Example: airtime 500 08012345678*";
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
           🎉🎉🎉 *SUCCESS!* 🎉🎉🎉\n\n✅ Your *{$amount}* airtime has been activated!\n\n📱 Recipient: *{$phone}*\n🌐 Network: *" . strtoupper($network) . "*\n💰 Amount Paid: ₦{$amount}\n\n🎁 Bonus Cashback: ₦{$cashback} credited to your wallet!\n\nEnjoy your airtime! 📡🚀";
        } else {
            $balance->balance += $amount;
            $balance->save();
            $transaction->update(['status' => 'ERROR']);
            $airtime->update(['status' => 'FAILED']);

            return "❌ Hmm, something went wrong with your purchase.\n\nYour balance of ₦{$amount} has been restored.\n\nPlease try again or contact support if the issue persists. 📞";
        }
    }

}
