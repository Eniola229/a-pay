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
use App\Http\Controllers\WebhookControllers\RegistrationController;
use App\Http\Controllers\WebhookControllers\UserValidationController;
use App\Http\Controllers\WebhookControllers\AirtimeController;
use App\Http\Controllers\WebhookControllers\DataController;
use App\Http\Controllers\WebhookControllers\TransferController;



class WhatsappController extends Controller
{

    protected $airtimeController;
    protected $dataController;
    protected $transferController;
    protected $registrationController;
    protected $userValidationController;

    public function __construct(
        RegistrationController $registrationController,
        UserValidationController $userValidationController,
        AirtimeController $airtimeController,
        DataController $dataController,
        TransferController $transferController
    )
    {
        $this->registrationController = $registrationController;
        $this->userValidationController = $userValidationController;
        $this->airtimeController = $airtimeController;
        $this->dataController = $dataController;
        $this->transferController = $transferController;
    }

    public function handle(Request $request)
    {
        $from = str_replace('whatsapp:', '', $request->input('From'));
        $message = strtolower(trim($request->input('Body')));

        // Check if user exists
        $user = User::where('mobile', $from)->first();

        // Handle new user registration
        if (!$user) {
            return $this->registrationController->handleRegistration($from, $message, $this);
        }

        // Validate existing user status and requirements
        $validationResponse = $this->userValidationController->validate($user, $from, $this);
        if ($validationResponse) {
            return $validationResponse;
        }

        // Process user command
        $response = $this->processCommand($user, $message);
        return $this->sendMessage($from, $response);
    }


    private function processCommand($user, $message)
    {
        // 1️⃣ Greetings
        if (in_array($message, [
            'hi', 'hello', 'hey', 'hey there', 'menu', 'help', 'Hi', 'Hello', 'Help',
            'good morning', 'good afternoon', 'good evening', 'good night', 'morning',
            'afternoon', 'evening', 'night', 'hola', 'yo', 'hiya', 'greetings', 'sup', 'what\'s up', 'apay', 'A-Pay', 'Hi A-Pay', 'hi a-pay'
        ])) {
            return $this->mainMenu($user);
        }

        // 2️⃣ Funding wallet
        // 1️⃣ Check balance FIRST (most specific)
        if (preg_match('/\b(balance|account\s+balance|check\s+balance|my\s+balance)\b/i', $message)) {
            $balance = Balance::where('user_id', $user->id)->first();
            if (!$balance) {
                return "❌ You don't have a balance yet.\nPlease fund your wallet first.";
            }
            $amount = number_format($balance->balance ?? 0, 2);
            return "💵 Your current wallet balance is: ₦{$amount}";
        }

        // 2️⃣ Check funding/deposit requests
        if (preg_match('/\b(fund|deposit|top\s*up|top-up|add\s+money)\b/i', $message)) {
            return 
                "💰 *TO FUND YOUR A-PAY WALLET*\n\n" .
                "🏦 *Bank:* Wema Bank\n" .
                "👤 *Account Name:* AFRICICL/" . strtoupper($user->name) . "\n" .
                "🔢 *Account Number:* {$user->account_number}\n\n" .
                "Transfer to the account above to top-up instantly.\n\n" .
                "__Kindly PIN this message to easily access it__";
        }

        //Upgrade account
        if (preg_match('/\b(upgrade|upgrad)(e|ed|ing)?\b/i', $message)) {
                if (!$user->hasKyc()) {
                    // User needs to complete KYC
                    $kycUrl = route('kyc.form', ['user' => $user->id, 'token' => encrypt($user->id)]);
                    
                    return
                        "⚠️ *KYC VERIFICATION* ⚠️\n\n".
                        "To upgrade your A-Pay account, please complete your KYC verification:\n\n".
                        "🔗 {$kycUrl}\n\n".
                        "📋 *Required Documents:*\n".
                        "• Passport Photo 📸\n".
                        "• BVN Number 🆔\n".
                        "• NIN Number 🆔\n".
                        "• Proof of Address 📄\n\n".
                        "⏱️ This takes only 5 minutes!";
                    
                } else {
                    return
                        "⚠️ *This A-Pay account has previously been upgraded.* ⚠️\n\n";
                }
        }
        // 3️⃣ Check account details request
        if (preg_match('/\b(account|account\s+(number|details|info))\b/i', $message)) {
            return 
                "💰 *YOUR VIRTUAL ACCOUNT DETAILS*\n\n" .
                "🏦 *Bank:* Wema Bank\n" .
                "👤 *Account Name:* AFRICICL/" . strtoupper($user->name) . "\n" .
                "🔢 *Account Number:* {$user->account_number}\n\n" .
                "Transfer to the account above to top-up instantly.\n\n" .
                "__Kindly PIN this message to easily access it__";
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
                    'mtn' => ['0803','0806','0703','0702','0706','0810','0813','0814','0816','0903','0906','0913','0916'],
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
                // Call the AirtimeController purchase method
                return $this->airtimeController->purchase($user, $network, $amount, $phone);
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
                        $session->delete(); // clear session after purchase
                        // Call the AirtimeController purchase method
                        return $this->airtimeController->purchase($user, $sessionData['network'], $amount, $phone);
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
            
            // Updated regex to capture FULL plan including minutes and duration
            preg_match('/(\d+(?:\.\d+)?(?:GB|MB|gb|mb)(?:\s*\+\s*[\d.]+\s*(?:min|mins|minutes))?\s*(?:-\s*\d+\s*(?:day|days|month|months|week|weeks))?)/i', $message, $planMatch);

            $phone = $phoneMatch[1] ?? null;
            $plan = $planMatch[1] ?? null;

            // ===User typed "data" but NO number ===
            if (!$phone) {
                return "🎉 Oh, you want to buy data? Great choice!\n\n📱 Send your phone number in this format:\n\n*data 09079916807*\n\nMake sure it's your correct phone number so we can send the data plans! 😊";
            }

            // === User has phone but NO plan - Show available plans ===
            if ($phone && !$plan) {
                // Auto-detect network from phone prefix using DataController
                $network = $this->dataController->detectNetwork($phone);

                if (!$network) {
                    return "⚠️ Invalid phone number. Please use a valid Nigerian number.";
                }

                // Get plans using DataController
                return $this->dataController->getPlans($network, $phone);
            }

            // === User has both phone AND plan - Process purchase ===
            if ($phone && $plan) {
                // Auto-detect network using DataController
                $network = $this->dataController->detectNetwork($phone);

                if (!$network) {
                    return "⚠️ Invalid phone number.";
                }

                // Process purchase using DataController
                return $this->dataController->purchase($user, $network, $phone, $plan);
            }

            return "⚠️ Please follow the format:\n*data 09079916807*";
        }
        
        // 6️⃣ Electricity
        if (preg_match('/(electric|bill|meter|electricity)/i', $message)) {

            // Handle cancel command
            if (preg_match('/\bcancel\b/i', $message)) {
                return "❌ Cancelled. Type 'menu' to see other options.";
            }

            // Extract meter number (10-11 digits)
            preg_match('/(\d{10,11})/', $message, $meterMatch);

            // Extract all numbers
            preg_match_all('/\d+/', $message, $allNumbers);

            // Extract provider/network if mentioned
            preg_match('/\b(abuja|eko|ibadan|ikeja|jos|kaduna|kano|portharcourt)\b/i', $message, $providerMatch);

            $meterNumber = $meterMatch[1] ?? null;
            $provider = isset($providerMatch[1]) ? strtolower($providerMatch[1]) : null;

            // Determine the amount (between 100-999999 and not the meter number)
            $amount = null;
            if (!empty($allNumbers[0])) {
                foreach ($allNumbers[0] as $num) {
                    $numInt = (int)$num;
                    if ($numInt >= 100 && $numInt <= 999999 && $num !== $meterNumber) {
                        $amount = (float)$num;
                        break;
                    }
                }
            }

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
                return "💰 Payment: *₦" . number_format($amount) . "* for meter *{$meterNumber}*\n\n📍 Which electricity provider?\n\n*abuja | eko | ibadan | ikeja | jos | kaduna | kano | portharcourt*\n\nExample: *electric {$meterNumber} {$amount} eko*";
            }

            // === CASE 4: All details provided - delegate to ElectricityController ===
            if ($meterNumber && $amount && $provider) {
                return app(\App\Http\Controllers\WebhookControllers\ElectricityController::class)
                    ->purchase($user, $meterNumber, $amount, $provider);
            }
        }


    $transferSession = WhatsappSession::where('user_id', $user->id)
                        ->where('context', 'transfer_confirm')
                        ->latest()
                        ->first();

    // If there's an active transfer session and user is confirming/canceling
    if ($transferSession && preg_match('/\b(confirm|yes|proceed|cancel|no)\b/i', $message)) {
        $sessionData = json_decode($transferSession->data ?? '{}', true) ?? [];
        
        // Handle cancel
        if (preg_match('/\b(cancel|no)\b/i', $message)) {
            $transferSession->delete();
            return "❌ Transfer cancelled. Type 'menu' to see other options.";
        }
        
        // Handle confirm
        if (preg_match('/\b(confirm|yes|proceed)\b/i', $message)) {
            $amount = $sessionData['amount'] ?? null;
            $recipient = $sessionData['recipient'] ?? null;
            
            if (!$amount || !$recipient) {
                $transferSession->delete();
                return "⚠️ Session expired. Please start a new transfer.";
            }
            
            $transferSession->delete();
            
            // Process the transfer
            $result = $this->transferController->transfer($user, $recipient, $amount);
            
            if ($result['success']) {
                // Send credit alert to recipient
                $creditAlertMsg = $this->transferController->sendCreditAlert(
                    $result['recipient'],
                    $user,
                    $amount,
                    $result['reference'],
                    $result['recipient_balance']
                );
                
                // Send the credit alert via WhatsApp
                $this->sendMessage($result['recipient']->mobile, $creditAlertMsg);
                
                return $result['message'];
            } else {
                return $result['message'];
            }
        }
    }

    if (preg_match('/\b(transfer|send|pay)\b/i', $message)) {

        // Handle cancel command
        if (preg_match('/\bcancel\b/i', $message)) {
            WhatsappSession::where('user_id', $user->id)
                ->where('context', 'transfer_confirm')
                ->delete();
            return "❌ Transfer cancelled. Type 'menu' to see other options.";
        }

        // Extract amount and recipient
        preg_match('/(\d+(?:\.\d{1,2})?)/', $message, $amountMatch);
        preg_match('/((?:\+?234|0)\d{10})/', $message, $phoneMatch);
        preg_match('/\b([1-9]\d{9})\b/', $message, $accountMatch);

        $amount = isset($amountMatch[1]) ? (float)$amountMatch[1] : null;
        $recipient = $phoneMatch[1] ?? $accountMatch[1] ?? null;

        // === CASE 1: No amount and no recipient - Show help ===
        if (!$amount && !$recipient) {
            return "💸 *Transfer Money*\n\n" .
                   "Send money to any A-Pay user instantly!\n\n" .
                   "📝 Format:\n" .
                   "*transfer [amount] [phone/account]*\n\n" .
                   "📱 Examples:\n" .
                   "• *transfer 5000 08012345678*\n" .
                   "• *send 5000 to +2348012345678*\n" .
                   "• *pay 5000 1234567890*\n\n" .
                   "Choose any format! 💚";
        }

        // === CASE 2: Has amount but no recipient ===
        if ($amount && !$recipient) {
            return "💰 You want to send *₦" . number_format($amount, 2) . "*\n\n" .
                   "📱 Who should receive it?\n\n" .
                   "Please provide the recipient's:\n" .
                   "• Phone number (e.g., 08012345678)\n" .
                   "• Or A-Pay account number (10 digits)\n\n" .
                   "Example: *transfer " . $amount . " 08012345678*";
        }

        // === CASE 3: Has recipient but no amount ===
        if ($recipient && !$amount) {
            $recipientUser = $this->transferController->findRecipient($recipient);
            
            if (!$recipientUser) {
                return "⚠️ Recipient not found.\n\n" .
                       "❌ *{$recipient}* is not registered on A-Pay.\n\n" .
                       "Please check the phone number or account number and try again.";
            }

            $recipientName = $recipientUser->name ?? 'A-Pay User';
            return "👤 Sending to: *{$recipientName}*\n" .
                   "📱 {$recipientUser->mobile}\n\n" .
                   "💰 How much would you like to send?\n\n" .
                   "Example: *transfer 5000 {$recipient}*";
        }

        // === CASE 4: Has both amount and recipient - Process transfer ===
        if ($amount && $recipient) {
            if ($amount < 50) {
                return "⚠️ Minimum transfer amount is ₦50.00\n\n" .
                       "Please enter an amount of ₦50 or more.";
            }

            $recipientUser = $this->transferController->findRecipient($recipient);
            
            if (!$recipientUser) {
                return "⚠️ Recipient not found.\n\n" .
                       "❌ *{$recipient}* is not registered on A-Pay.\n\n" .
                       "Please check and try again with the correct:\n" .
                       "• Phone number (e.g., 08012345678)\n" .
                       "• Or account number (10 digits)";
            }

            // Check for confirmation session
            $session = WhatsappSession::where('user_id', $user->id)
                        ->where('context', 'transfer_confirm')
                        ->latest()
                        ->first();

            $sessionData = json_decode($session->data ?? '{}', true) ?? [];

            // If no session or different transfer, ask for confirmation
            if (!$session || 
                $sessionData['amount'] != $amount || 
                $sessionData['recipient'] != $recipient) {
                
                if (!$session) {
                    $session = new WhatsappSession();
                    $session->id = Str::uuid();
                    $session->user_id = $user->id;
                    $session->context = 'transfer_confirm';
                }

                $session->data = json_encode([
                    'amount' => $amount,
                    'recipient' => $recipient,
                    'recipient_id' => $recipientUser->id
                ]);
                $session->save();

                $recipientName = $recipientUser->name ?? 'A-Pay User';
                return "⚠️ *CONFIRM TRANSFER*\n\n" .
                       "You're about to send:\n" .
                       "💰 Amount: *₦" . number_format($amount, 2) . "*\n\n" .
                       "To:\n" .
                       "👤 Name: *{$recipientName}*\n" .
                       "📱 Phone: {$recipientUser->mobile}\n" .
                       "🔢 Account: {$recipientUser->account_number}\n\n" .
                       "Reply with:\n" .
                       "• *confirm or yes* to proceed\n" .
                       "• *cancel* to abort";
            }

            // Note: Confirmation is handled at the top of this section
            // The session check above will catch "confirm" or "yes" responses
        }

        return "⚠️ Please follow the correct format:\n\n*transfer [amount] [phone/account]*\n\nExample: *transfer 5000 08012345678*";
    }

        // 7️⃣ Support / Customer Care
            if (preg_match('/(support|customer\s*care|help|agent|contact|complain)/i', $message)) {
                return "💚 *A-Pay Support Team*\n\nIf you need assistance, please contact our support via WhatsApp:\n👉 *+234-803-590-6313*\n\nWe’re available to help you resolve any issue as quickly as possible.\n\nIf you’d like to return to the *main menu*, simply type:\n➡️ *menu*";
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
               "▶️ airtime — Buy Airtime\n" .
               "▶️ data — Buy Data\n" .
               "▶️ electric — Pay Electricity Bill\n" .
               "▶️ transfer to A-Pay — Send money to another A-Pay account\n" .
               "▶️ fund — Fund Wallet\n" .
               "▶️ balance — View Wallet Balance\n" .
               "▶️ transactions — View Recent Transactions\n" .
               "▶️ upgrade — Upgrade your A-Pay account\n\n" .
               "💬 *Support / Customer Care*\n" .
               "If you need assistance, please contact us on WhatsApp:\n" .
               "👉 *+234-803-590-6313*\n\n" .
               "We’re always ready to help you with any issue.\n\n" .
               "*Example: airtime 500 08012345678*";
    }



    private function extractAmount($text)
    {
        preg_match('/\d+/', $text, $match);
        return $match[0] ?? null;
    }

    public function sendMessage($to, $body)
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

  

}
