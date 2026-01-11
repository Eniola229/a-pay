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
use App\Models\WhatsappMessage;
use App\Models\ElectricityPurchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\WebhookControllers\RegistrationController;
use App\Http\Controllers\WebhookControllers\UserValidationController;
use App\Http\Controllers\WebhookControllers\AirtimeController;
use App\Http\Controllers\WebhookControllers\DataController;
use App\Http\Controllers\WebhookControllers\TransferController;
use App\Http\Controllers\WebhookControllers\ElectricityController;

class WhatsappController extends Controller
{
    protected $airtimeController;
    protected $dataController;
    protected $transferController;
    protected $registrationController;
    protected $userValidationController;

    // Session timeout in seconds (1 minute as requested)
    private const SESSION_TIMEOUT = 60;

    // Intent definitions with fuzzy matching support
    private $intents = [
        'greeting' => ['hi', 'hello', 'hey', 'menu', 'help', 'start', 'morning', 'afternoon', 'evening', 'night', 'apay', 'a-pay'],
        'check_balance' => ['balance', 'wallet', 'account balance', 'check balance', 'my balance'],
        'fund_wallet' => ['fund', 'deposit', 'top up', 'topup', 'add money', 'recharge wallet'],
        'account_details' => ['account', 'account number', 'account details', 'account info', 'virtual account'],
        'upgrade_account' => ['upgrade', 'kyc', 'verify account', 'verification'],
        'buy_airtime' => ['airtime', 'recharge', 'credit', 'buy airtime', 'load'],
        'buy_data' => ['data', 'internet', 'subscription', 'data plan', 'buy data'],
        'electricity' => ['electric', 'electricity', 'bill', 'meter', 'light', 'nepa', 'phcn'],
        'transfer' => ['transfer', 'send', 'pay', 'send money', 'payment'],
        'transactions' => ['transactions', 'history', 'transaction history', 'my transactions'],
        'support' => ['support', 'customer care', 'agent', 'contact', 'complain', 'complaint'],
        'thank_you' => ['thank you', 'thanks', 'thx', 'sharp', 'appreciate'],
        'about_founder' => ['founder', 'ceo', 'president', 'who created', 'who made'],
        'about_company' => ['registered', 'registration', 'cac', 'company'],
        'about_apay' => ['what is apay', 'what is a-pay', 'about apay', 'tell me about']
    ];

    // Network prefixes for auto-detection
    private $networkPrefixes = [
        'mtn' => ['0803','0806','0703','0702','0706','0704','0810','0813','0814','0816','0903','0906','0913','0916'],
        'glo' => ['0805','0807','0811','0705','0815','0905','0915'],
        'airtel' => ['0802','0808','0708','0812','0701','0902','0907','0901','0912','0904'],
        '9mobile' => ['0809','0817','0818','0909','0908']
    ];

    public function __construct(
        RegistrationController $registrationController,
        UserValidationController $userValidationController,
        AirtimeController $airtimeController,
        DataController $dataController,
        TransferController $transferController
    ) {
        $this->registrationController = $registrationController;
        $this->userValidationController = $userValidationController;
        $this->airtimeController = $airtimeController;
        $this->dataController = $dataController;
        $this->transferController = $transferController;
    }

    public function handle(Request $request)
    {
        $from = str_replace('whatsapp:', '', $request->input('From'));
        $message = trim($request->input('Body'));
        $messageSid = $request->input('MessageSid');
    
        // Log incoming message
        WhatsappMessage::create([
            'phone_number' => $from,
            'direction' => 'incoming',
            'message_body' => $message,
            'message_sid' => $messageSid,
            'status' => 'received',
            'metadata' => [
                'received_at' => now()->toIso8601String(),
                'profile_name' => $request->input('ProfileName'),
            ]
        ]);

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

        // Process user command with improvements
        $response = $this->processCommand($user, $message);
        return $this->sendMessage($from, $response);
    }

    /**
     * IMPROVED: Main command processor with intelligent routing
     */
    private function processCommand($user, $message)
    {
        // Rate limiting protection
        if (RateLimiter::tooManyAttempts("whatsapp:{$user->id}", 30)) {
            return "⚠️ Too many requests. Please wait a moment before trying again.";
        }
        RateLimiter::hit("whatsapp:{$user->id}", 60);

        // Normalize message
        $normalizedMessage = $this->normalizeMessage($message);

        // Handle global cancel command
        if ($this->isCancelCommand($normalizedMessage)) {
            return $this->handleCancel($user);
        }

        // Classify intent with fuzzy matching
        $intent = $this->classifyIntent($normalizedMessage);

        // Extract entities from message
        $entities = $this->extractEntities($message);

        // Route to appropriate handler
        return $this->routeIntent($user, $intent, $message, $normalizedMessage, $entities);
    }

    /**
     * Normalize message for better processing
     */
    private function normalizeMessage($message)
    {
        $message = strtolower(trim($message));
        $message = preg_replace('/\s+/', ' ', $message);
        return $message;
    }

    /**
     * Check if message is a cancel command
     */
    private function isCancelCommand($message)
    {
        return preg_match('/\b(cancel|stop|abort|nevermind|back)\b/i', $message);
    }

    /**
     * Handle cancel command
     */
    private function handleCancel($user)
    {
        WhatsappSession::where('user_id', $user->id)->delete();
        return "❌ Cancelled. Type *menu* to see available options.";
    }

    /**
     * ADVANCED: Natural language intent classification
     */
    private function classifyIntent($message)
    {
        // Check for multi-keyword combinations (highest priority)
        $combinedPatterns = [
            'buy_airtime' => [
                '/\b(buy|purchase|get|want|need|load|recharge|credit)\b.*\b(airtime|recharge|credit)\b.*\b(to|for|on|at)\b.*\b(\d{10,11})\b/i',
                '/\b(airtime|recharge|credit)\b.*\b(to|for|on|at)\b.*\b(\d{10,11})\b/i',
                '/\b(send|transfer)\b.*\b(airtime|recharge|credit|load)\b.*\b(to|for)\b/i',
                '/\b(top\s*up)\b.*\b(\d{10,11})\b.*\b(airtime|credit)\b/i',
                '/\b(recharge|credit|load)\b.*\b(\d{10,11})\b/i',
                '/\b(airtime|recharge|credit|load)\b/i'
            ],
            'buy_data' => [
                '/\b(buy|purchase|get|want|need|subscribe)\b.*\b(data|internet|bundle|package|plan)\b.*\b(to|for|on|at)\b.*\b(\d{10,11})\b/i',
                '/\b(data|internet|bundle)\b.*\b(to|for|on|at)\b.*\b(\d{10,11})\b/i',
                '/\b(send|transfer)\b.*\b(data|internet|bundle)\b.*\b(to|for)\b/i',
                '/\b(subscribe|sub)\b.*\b(data|internet|bundle)\b/i',
                '/\b(data|internet|bundle|package|subscription)\b/i'
            ],
            'electricity' => [
                '/\b(pay|buy|purchase|renew)\b.*\b(electricity|electric|light|power|bill|nepa|phcn)\b.*\b(for|on|meter)\b.*\b(\d{10,13})\b/i',
                '/\b(electricity|electric|light|power|bill|nepa|phcn)\b.*\b(for|meter)\b.*\b(\d{10,13})\b/i',
                '/\b(buy|get|purchase)\b.*\b(light|power|current)\b/i',
                '/\b(pay|settle)\b.*\b(bill|nepa|phcn)\b/i',
                '/\b(electricity|electric|meter|nepa|phcn|power|light)\b/i'
            ],
            'transfer' => [
                '/\b(send|transfer)\b.*\b(to|for)\b.*\b(\d{10,11})\b(?!.*\b(airtime|data|electric|light|power|meter|bill|internet)\b)/i',
                '/\b(transfer|send)\b.*\b(money|funds|cash|balance)\b/i',
                '/\b(pay)\b.*\b(\d{10,11})\b(?!.*\b(airtime|data|electric|light|power|meter|bill|internet)\b)/i'
            ],
            'fund_wallet' => [
                '/\b(fund|deposit|add|top\s*up|recharge)\b.*\b(wallet|account|balance)\b/i',
                '/\b(add\s*money|credit\s*account)\b/i'
            ],
            'check_balance' => [
                '/\b(check|see|view|what\s*is|how\s*much|show\s*me)\b.*\b(balance|wallet|account|money)\b/i'
            ],
            'account_details' => [
                // "what is my account details", "what is my account number"
                '/\b(what\s*is|what\s*\'s|tell\s*me|give\s*me)\b.*\b(account)\s*(details|number|info|information)\b/i',
                // "show me my account", "view my account", "display my account"
                '/\b(show|view|display|see|get|check)\b.*\b(my\s*)?(account)\s*(details|number|info|information)\b/i',
                // "account details", "account number", "account info"
                '/\b(account)\s*(details|number|info|information)\b/i',
                // "virtual account", "virtual account number", "virtual account details"
                '/\b(virtual)\s*account\s*(details|number|info)?\b/i',
                // "my account"
                '/\b(my)\s*account\b/i'
            ]
        ];
        
        // Check for combined patterns first
        foreach ($combinedPatterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    return $intent;
                }
            }
        }
        
        // Fallback to keyword scoring
        $scores = [];
        foreach ($this->intents as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $score += 10;
                    if (strpos($message, $keyword) !== false) {
                        $score += 5;
                    }
                } else {
                    similar_text($message, $keyword, $percent);
                    if ($percent > 70) {
                        $score += ($percent / 10);
                    }
                }
            }
            if ($score > 0) {
                $scores[$intent] = $score;
            }
        }
        
        if (empty($scores)) {
            return 'unknown';
        }
        
        arsort($scores);
        return array_key_first($scores);
    }

    /**
     * ENHANCED: Extract all relevant entities from natural language messages
     */
    private function extractEntities($message)
    {
        $entities = [
            'phone' => $this->extractPhone($message),
            'amount' => $this->extractAmount($message),
            'network' => $this->extractNetwork($message),
            'meter_number' => $this->extractMeterNumber($message),
            'provider' => $this->extractProvider($message),
            'data_plan' => $this->extractDataPlan($message),
            'recipient' => $this->extractRecipient($message)
        ];
        
        // Extract amount after "of" or "for"
        if (preg_match('/\b(of|for|worth|amounting)\s+(\d+(?:\.\d{1,2})?)\b/i', $message, $match)) {
            $amount = (float)$match[2];
            if ($amount >= 10 && $amount <= 10000000) {
                $entities['amount'] = $amount;
            }
        }
        
        // Extract "airtime [number] of 1000"
        if (preg_match('/\b(\d{10,11})\s+(?:of|for)\s+(\d+(?:\.\d{1,2})?)\b/i', $message, $match)) {
            $entities['phone'] = '0' . substr($match[1], -10);
            $amount = (float)$match[2];
            if ($amount >= 10 && $amount <= 10000000) {
                $entities['amount'] = $amount;
            }
        }
        
        // Extract "1000 naira" or "1000 NGN"
        if (preg_match('/\b(\d{2,7})\s*(?:naira|ngn|₦)\b/i', $message, $match)) {
            $amount = (float)$match[1];
            if ($amount >= 10 && $amount <= 10000000) {
                $entities['amount'] = $amount;
            }
        }
        
        // Extract "this number 09012345678"
        if (preg_match('/\b(?:this\s+number|number|to)\s+(\d{10,11})\b/i', $message, $match)) {
            $phone = '0' . substr($match[1], -10);
            if (preg_match('/^0\d{10}$/', $phone)) {
                $entities['phone'] = $phone;
                $entities['recipient'] = $phone;
            }
        }
        
        // Extract "meter number 1234567890"
        if (preg_match('/\b(?:meter\s*(?:number)?|meter\s*no\.?)\s*(\d{10,13})\b/i', $message, $match)) {
            $entities['meter_number'] = $match[1];
        }
        
        return $entities;
    }

    /**
     * ENHANCED: Extract phone number with multiple format support
     */
    private function extractPhone($message)
    {
        $patterns = [
            '/(?:\+?234[-\s]?)([789]\d{9})\b/',
            '/\b(0[789]\d{9})\b/',
            '/\b(?:to|for|at|on|send|transfer)\s+([789]\d{9})\b/i',
            '/[\[\(]?([789]\d{9})[\]\)]?/',
            '/\b(?:number|no\.?|phone|mobile)\s*:?\s*([789]\d{9})\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $match)) {
                $phone = '0' . substr($match[1], -10);
                if (preg_match('/^0\d{10}$/', $phone)) {
                    return $phone;
                }
            }
        }
        return null;
    }

    /**
     * ENHANCED: Extract amount with better natural language pattern matching
     */
    private function extractAmount($message)
    {
        $message = str_replace(',', '', $message);
        
        $patterns = [
            '/(?:₦|NGN|naira|N)\s*(\d+(?:\.\d{1,2})?)/i',
            '/\b(?:of|for|worth|cost|price|amounting)\s*(\d+(?:\.\d{1,2})?)\b/i',
            '/\b(?:amount|sum|total)\s*(?:of\s*)?(\d+(?:\.\d{1,2})?)\b/i',
            '/\b(\d{3,7})(?:\s|$|,|\.|;)/',
            '/(\d+(?:\.\d{1,2})?)\s*(?:naira|ngn|₦)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $match)) {
                $amount = (float)$match[1];
                if ($amount >= 10 && $amount <= 10000000) {
                    return $amount;
                }
            }
        }
        return null;
    }

    /**
     * Extract network from message
     */
    private function extractNetwork($message)
    {
        $networks = ['mtn', 'glo', 'airtel', '9mobile'];
        foreach ($networks as $network) {
            if (stripos($message, $network) !== false) {
                return $network;
            }
        }
        return null;
    }

    /**
     * Extract meter number (distinct from phone numbers)
     */
    private function extractMeterNumber($message)
    {
        if (preg_match('/\b(\d{10,13})\b/', $message, $match)) {
            $num = $match[1];
            if (strlen($num) >= 10 && !preg_match('/^0[789]/', $num)) {
                return $num;
            }
        }
        return null;
    }

    /**
     * Extract electricity provider
     */
    private function extractProvider($message)
    {
        $providers = ['abuja', 'eko', 'ibadan', 'ikeja', 'jos', 'kaduna', 'kano', 'portharcourt'];
        foreach ($providers as $provider) {
            if (stripos($message, $provider) !== false) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Extract data plan from message
     */
    private function extractDataPlan($message)
    {
        if (preg_match('/(\d+(?:\.\d+)?(?:GB|MB|gb|mb)(?:\s*\+\s*[\d.]+\s*(?:min|mins|minutes))?\s*(?:-\s*\d+\s*(?:day|days|month|months|week|weeks))?)/i', $message, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * NEW: Extract data plan number (for numbered selection)
     * Handles formats like "2", "data 5", "3 0907991607", "data 2 0907991607"
     */
    private function extractDataPlanNumber($message)
    {
        $normalized = strtolower(trim($message));
        
        // Match standalone number (e.g., "2", "5")
        if (preg_match('/^(\d+)$/', $normalized, $match)) {
            $num = (int)$match[1];
            return ($num >= 1 && $num <= 50) ? $num : null;
        }
        
        // Match "data 2" or similar
        if (preg_match('/(?:data|internet|bundle)\s+(\d+)/i', $normalized, $match)) {
            $num = (int)$match[1];
            return ($num >= 1 && $num <= 50) ? $num : null;
        }
        
        return null;
    }

    /**
     * Extract recipient (phone or account number)
     */
    private function extractRecipient($message)
    {
        $phone = $this->extractPhone($message);
        if ($phone) {
            return $phone;
        }
        
        if (preg_match('/\b([1-9]\d{9})\b/', $message, $match)) {
            return $match[1];
        }
        
        return null;
    }

    /**
     * IMPROVED: Auto-detect network from phone prefix
     */
    private function detectNetworkFromPhone($phone)
    {
        if (!$phone) return null;
        
        $prefix = substr($phone, 0, 4);
        
        foreach ($this->networkPrefixes as $network => $prefixes) {
            if (in_array($prefix, $prefixes)) {
                return $network;
            }
        }
        return null;
    }

    /**
     * NEW: Check if message contains ONLY a phone number
     */
    private function isOnlyPhoneNumber($message)
    {
        $normalized = preg_replace('/[\s]+/', '', $message);
        return preg_match('/^0[789]\d{9}$/', $normalized);
    }

    /**
     * NEW: Check if message contains ONLY an amount
     */
    private function isOnlyAmount($message)
    {
        $normalized = strtolower(trim($message));
        // Remove currency symbols
        $normalized = preg_replace('/[₦nngn]/i', '', $normalized);
        // Remove common words
        $normalized = preg_replace('/\b(naira|amount|price|cost)\b/i', '', $normalized);
        $normalized = trim($normalized);
        
        // Check if it's just a number
        return preg_match('/^\d+$/', $normalized);
    }

    /**
     * Check if a session is still valid (not expired)
     */
    private function isSessionValid($session)
    {
        if (!$session) {
            return false;
        }
        
        $updatedAt = $session->updated_at ?? $session->created_at;
        $now = now();
        
        return $now->diffInSeconds($updatedAt) < self::SESSION_TIMEOUT;
    }

    /**
     * Get or create valid session (handles expired sessions)
     */
    private function getOrCreateSession($user, $context)
    {
        $session = WhatsappSession::where('user_id', $user->id)
            ->where('context', $context)
            ->latest()
            ->first();
        
        if ($session && $this->isSessionValid($session)) {
            return $session;
        }
        
        if ($session) {
            $session->delete();
        }
        
        $session = new WhatsappSession();
        $session->id = Str::uuid();
        $session->user_id = $user->id;
        $session->context = $context;
        $session->data = json_encode([]);
        $session->save();
        
        return $session;
    }

    /**
     * Route intent to appropriate handler
     */
    private function routeIntent($user, $intent, $originalMessage, $normalizedMessage, $entities)
    {
        switch ($intent) {
            case 'greeting':
                return $this->mainMenu($user);

            case 'check_balance':
                return $this->handleCheckBalance($user);

            case 'fund_wallet':
                return $this->handleFundWallet($user);

            case 'account_details':
                return $this->handleAccountDetails($user);

            case 'upgrade_account':
                return $this->handleUpgradeAccount($user);

            case 'buy_airtime':
                return $this->handleAirtimeFlow($user, $originalMessage, $entities);

            case 'buy_data':
                return $this->handleDataFlow($user, $originalMessage, $entities);

            case 'electricity':
                return $this->handleElectricityFlow($user, $originalMessage, $entities);

            case 'transfer':
                return $this->handleTransferFlow($user, $originalMessage, $entities);

            case 'transactions':
                return $this->handleTransactions($user);

            case 'support':
                return $this->handleSupport();

            case 'thank_you':
                return $this->handleThankYou();

            case 'about_founder':
                return $this->handleAboutFounder();

            case 'about_company':
                return $this->handleAboutCompany();

            case 'about_apay':
                return $this->handleAboutApay();

            default:
                return $this->handleUnknown();
        }
    }

    /**
     * Handle check balance
     */
    private function handleCheckBalance($user)
    {
        $balance = Balance::where('user_id', $user->id)->first();
        if (!$balance) {
            return "❌ You don't have a balance yet.\nPlease fund your wallet first.\n\nType *fund* for funding instructions.";
        }
        $amount = number_format($balance->balance ?? 0, 2);
        return "💵 Your current wallet balance is: ₦{$amount}\n\nType *menu* to see what you can do!";
    }

    /**
     * Handle fund wallet
     */
    private function handleFundWallet($user)
    {
        return 
            "💰 *TO FUND YOUR A-PAY WALLET*\n\n" .
            "🏦 *Bank:* Wema Bank\n" .
            "👤 *Account Name:* AFRICICL/" . strtoupper($user->name) . "\n" .
            "🔢 *Account Number:* {$user->account_number}\n\n" .
            "Transfer to the virtual account above to top-up instantly.\n\n" .
            "__Kindly PIN this message to easily access it__";
    }

    /**
     * Handle account details
     */
    private function handleAccountDetails($user)
    {
        return 
            "💰 *YOUR VIRTUAL ACCOUNT DETAILS*\n\n" .
            "🏦 *Bank:* Wema Bank\n" .
            "👤 *Account Name:* AFRICICL/" . strtoupper($user->name) . "\n" .
            "🔢 *Account Number:* {$user->account_number}\n\n" .
            "Transfer to the account above to top-up instantly.\n\n" .
            "__Kindly PIN this message to easily access it__";
    }

    /**
     * Handle upgrade account
     */
    private function handleUpgradeAccount($user)
    {
        if (!$user->hasKyc()) {
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
            return "✅ Your A-Pay account has already been upgraded!\n\nType *menu* for available options.";
        }
    }

    /**
     * ENHANCED: Handle airtime with conversational flow and session timeout
     * Now accepts phone number or amount separately during active session
     */
    private function handleAirtimeFlow($user, $message, $entities)
    {
        $session = $this->getOrCreateSession($user, 'airtime');
        $sessionData = json_decode($session->data ?? '{}', true) ?? [];
        
        // Check if message contains ONLY a phone number or ONLY an amount
        // This is for when user responds with partial information in an active session
        $isOnlyPhone = $this->isOnlyPhoneNumber($message);
        $isOnlyAmount = $this->isOnlyAmount($message);
        
        if (!empty($sessionData) && ($isOnlyPhone || $isOnlyAmount)) {
            // User is responding to a session prompt
            if ($isOnlyPhone) {
                $phone = $entities['phone'];
                $amount = $sessionData['amount'] ?? null;
                $network = $entities['network'] ?? $sessionData['network'] ?? null;
                
                // Auto-detect network if phone provided but network isn't
                if ($phone && !$network) {
                    $network = $this->detectNetworkFromPhone($phone);
                }
            } elseif ($isOnlyAmount) {
                $amount = $entities['amount'];
                $phone = $sessionData['phone'] ?? null;
                $network = $entities['network'] ?? $sessionData['network'] ?? null;
            }
        } else {
            // Merge extracted entities with session data
            $phone = $entities['phone'] ?? $sessionData['phone'] ?? null;
            $amount = $entities['amount'] ?? $sessionData['amount'] ?? null;
            $network = $entities['network'] ?? $sessionData['network'] ?? null;
            
            // Auto-detect network if phone provided but network isn't
            if ($phone && !$network) {
                $network = $this->detectNetworkFromPhone($phone);
            }
        }
        
        // Update session with new data
        $this->updateSessionData($session, [
            'phone' => $phone,
            'amount' => $amount,
            'network' => $network
        ]);
        
        // Check if we have all required information
        if ($phone && $amount && $network) {
            $session->delete();
            return $this->airtimeController->purchase($user, $network, $amount, $phone);
        }
        
        // Check if session is new (user just started)
        $isNewSession = empty($sessionData);
        
        // Generate helpful responses based on what's missing
        if (!$phone && !$amount && !$network) {
            return $this->getAirtimeHelpMessage($isNewSession);
        }
        
        if ($phone && !$amount) {
            return $this->getAirtimeAmountRequest($phone, $network, $isNewSession);
        }
        
        if ($phone && $amount && !$network) {
            return $this->getAirtimeNetworkRequest($phone, $amount, $isNewSession);
        }
        
        if (!$phone && $amount) {
            return $this->getAirtimePhoneRequest($amount, $network, $isNewSession);
        }
        
        return $this->getAirtimeHelpMessage($isNewSession);
    }

    /**
     * Get helpful airtime instructions message
     */
    private function getAirtimeHelpMessage($isNewSession = true)
    {
        $timeoutNotice = $isNewSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "📱 To buy airtime, you can say it in your own words!\n\n" .
               "Examples:\n" .
               "• *I want to buy airtime to 09079916807 of 1000*\n" .
               "• *Send 500 airtime to 09079916807*\n" .
               "• *Recharge my number with 1000*\n" .
               "• *Load 2000 credit for 09079916807*\n" .
               "• *Airtime 500 09079916807*\n\n" .
               $timeoutNotice;
    }

    /**
     * Get request for airtime amount
     */
    private function getAirtimeAmountRequest($phone, $network, $isNewSession = false)
    {
        $networkText = $network ? " *" . strtoupper($network) . "* " : " ";
        $timeoutNotice = $isNewSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "🎯 You want to buy" . $networkText . "airtime for *{$phone}*\n\n" .
               "💡 How much airtime do you want to buy?\n\n" .
               "Examples:\n" .
               "• *airtime 1000*\n" .
               "• *airtime 500*\n" .
               "• *airtime 2000*" .
               $timeoutNotice;
    }

    /**
     * Get request for airtime network
     */
    private function getAirtimeNetworkRequest($phone, $amount, $isNewSession = false)
    {
        $timeoutNotice = $isNewSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "💰 You want to buy *₦" . number_format($amount) . "* airtime for *{$phone}*\n\n" .
               "📶 Which network is this number on?\n\n" .
               "Examples:\n" .
               "• *airtime MTN*\n" .
               "• *airtime Airtel*\n" .
               "• *airtime Glo*\n" .
               "• *airtime 9mobile*" .
               $timeoutNotice;
    }

    /**
     * Get request for airtime phone number
     */
    private function getAirtimePhoneRequest($amount, $network, $isNewSession = false)
    {
        $networkText = $network ? " *" . strtoupper($network) . "* " : " ";
        $timeoutNotice = $isNewSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "💰 You want to buy *₦" . number_format($amount) . "*" . $networkText . "airtime\n\n" .
               "📱 Which number should I send it to?\n\n" .
               "Examples:\n" .
               "• *airtime 09079916807*\n" .
               $timeoutNotice;
    }

    /**
     * ENHANCED: Handle data with conversational flow and session timeout
     * Now supports numbered plan selection: "2 0907991607" or "data 5 0907991607"
     */
    private function handleDataFlow($user, $message, $entities)
    {
        $phone = $entities['phone'];
        $plan = $entities['data_plan'];
        $network = $entities['network'];
        
        // Check for active session
        $session = WhatsappSession::where('user_id', $user->id)
            ->where('context', 'data')
            ->latest()
            ->first();
        
        $sessionData = [];
        $hasValidSession = false;
        
        if ($session && $this->isSessionValid($session)) {
            $sessionData = json_decode($session->data ?? '{}', true) ?? [];
            $hasValidSession = true;
        }

        // Check if user is selecting a plan by number (e.g., "2" or "data 5")
        // This check comes FIRST to prevent showing plans again
        $planNumber = $this->extractDataPlanNumber($message);
        
        if ($planNumber !== null) {
            // User sent a number - check if we have plans in session or fetch them
            if (isset($sessionData['plans'])) {
                $plans = $sessionData['plans'];
                // Use phone from message or session
                $phone = $phone ?? $sessionData['phone'] ?? null;
                $network = $network ?? $sessionData['network'] ?? null;
                
                if ($phone && $network) {
                    // Validate plan number
                    if (isset($plans[$planNumber - 1])) {
                        $selectedPlan = $plans[$planNumber - 1];
                        
                        // Clear session after purchase
                        WhatsappSession::where('user_id', $user->id)
                            ->where('context', 'data')
                            ->delete();
                        
                        return $this->dataController->purchase($user, $network, $phone, $selectedPlan['data_plan']);
                    } else {
                        return "⚠️ Invalid plan number. Please select a number between 1 and " . count($plans);
                    }
                }
                } elseif ($phone) {
                    // User sent number and phone but no plans in session
                    // Fetch plans and try to process
                    $detectedNetwork = $this->detectNetworkFromPhone($phone);
                    if (!$detectedNetwork) {
                        return "⚠️ Invalid phone number. Please use a valid Nigerian number.";
                    }
                    
                    // Fetch plans directly without caching
                    $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
                    $allPlans = $response->json()['data'] ?? [];
                    $plans = collect($allPlans)->where('service_id', strtolower($detectedNetwork))->values()->toArray();
                    
                    if (empty($plans)) {
                        return "⚠️ No data plans found for *" . strtoupper($detectedNetwork) . "*.";
                    }
                    
                    if (isset($plans[$planNumber - 1])) {
                        $selectedPlan = $plans[$planNumber - 1];
                        
                        // Clear session
                        WhatsappSession::where('user_id', $user->id)
                            ->where('context', 'data')
                            ->delete();
                        
                        return $this->dataController->purchase($user, $detectedNetwork, $phone, $selectedPlan['data_plan']);
                    } else {
                        return "⚠️ Invalid plan number. Please select a number between 1 and " . count($plans);
                    }
                }
        }

        // If only network mentioned, show plans
        if ($network && !$phone && !$plan) {
            return $this->showDataPlansForNetwork($network, $user);
        }

        // No phone provided - check for active session
        if (!$phone) {
            if ($hasValidSession) {
                $phone = $sessionData['phone'] ?? null;
            }
            
            if (!$phone) {
                return "🎉 Oh, you want to buy data? Great choice!\n\n" .
                       "📱 Send your phone number:\n\n" .
                       "*data 09079916807*\n\n" .
                       "Make sure it's correct! 😊\n\n" .
                       "⏱️ *Please respond within 1 minute.*";
            }
        }

        // Phone provided but no plan string (and no plan number from check above)
        if ($phone && !$plan) {
            $detectedNetwork = $this->detectNetworkFromPhone($phone);
            if (!$detectedNetwork) {
                return "⚠️ Invalid phone number. Please use a valid Nigerian number.";
            }
            
            // This shows plans AND stores them in session
            return $this->showDataPlansForPhone($detectedNetwork, $phone, $session);
        }

        // Both phone and plan string provided
        if ($phone && $plan) {
            $detectedNetwork = $this->detectNetworkFromPhone($phone);
            if (!$detectedNetwork) {
                return "⚠️ Invalid phone number.";
            }
            
            // Clear session after purchase
            WhatsappSession::where('user_id', $user->id)
                ->where('context', 'data')
                ->delete();
            
            return $this->dataController->purchase($user, $detectedNetwork, $phone, $plan);
        }

        return "⚠️ Please follow the format:\n*data 09079916807*";
    }

    /**
     * Show numbered data plans for specific network and store in session
     */
    private function showDataPlansForPhone($network, $phone, $session)
    {
        // Fetch plans directly without caching
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
        $allPlans = $response->json()['data'] ?? [];
        $plans = collect($allPlans)->where('service_id', strtolower($network))->values()->toArray();
        
        if (empty($plans)) {
            return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
        }
        
        // Store plans in session for numbered selection
        $this->updateSessionData($session, [
            'phone' => $phone,
            'plans' => $plans
        ]);
        
        $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans for *{$phone}*:\n\n";
        foreach (array_slice($plans, 0, 50) as $index => $p) {
            $planListMsg .= ($index + 1) . ". " . $p['data_plan'] . " - ₦" . number_format($p['price']) . "\n";
        }
        $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n" .
                       "📝 Reply with just the number and your phone number:\n" .
                       " *data 2 0907916807*\n\n" .
                       " *data [number] [phone number]*\n\n" .
                       "⏱️ *Please respond within 1 minute.*";
        return $planListMsg;
    }

    /**
     * Show data plans for specific network (when only network is mentioned)
     */
    private function showDataPlansForNetwork($network, $user)
    {
        // Fetch plans directly without caching
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');
        $allPlans = $response->json()['data'] ?? [];
        $plans = collect($allPlans)->where('service_id', strtolower($network))->values()->toArray();
        
        if (empty($plans)) {
            return "⚠️ No data plans found for *" . strtoupper($network) . "*.";
        }
        
        // Get or create session for data context
        $session = $this->getOrCreateSession($user, 'data');
        
        // Store plans in session for when user replies with selection
        $this->updateSessionData($session, [
            'plans' => $plans,
            'network' => $network
        ]);
        
        // Display numbered plans
        $planListMsg = "💾 Available *" . strtoupper($network) . "* data plans:\n\n";
        
        $displayPlans = array_slice($plans, 0, 50);
        foreach ($displayPlans as $index => $p) {
            $planListMsg .= ($index + 1) . ". " . $p['data_plan'] . " - ₦" . number_format($p['price']) . "\n";
        }
        
        // Show more indicator if there are more plans
        if (count($plans) > 50) {
            $planListMsg .= "\n💡 Showing first 50 plans.\n";
        }
        
        // Instructions with both formats
        $planListMsg .= "\n✨ Which plan catches your eye? 👀\n\n";
        $planListMsg .= "📝 Reply with this format:\n\n";
        $planListMsg .= "*data 2 09079916807* \n";
        $planListMsg .= "or \n";
        $planListMsg .= "*data 09079916807 2*\n\n";
        $planListMsg .= "*data [number] [phone number]*\n\n";
        $planListMsg .= "⏱️ *Please respond within 1 minute.*";
        
        return $planListMsg;
    }

    /**
     * ENHANCED: Handle electricity with conversational flow and session timeout
     */
    private function handleElectricityFlow($user, $message, $entities)
    {
        $meterNumber = $entities['meter_number'];
        $amount = $entities['amount'];
        $provider = $entities['provider'];
        
        // Check for active session
        $session = WhatsappSession::where('user_id', $user->id)
            ->where('context', 'electricity')
            ->latest()
            ->first();
        
        $sessionData = [];
        $hasValidSession = false;
        
        if ($session && $this->isSessionValid($session)) {
            $sessionData = json_decode($session->data ?? '{}', true) ?? [];
            $hasValidSession = true;
            
            // Merge with entities
            $meterNumber = $meterNumber ?? $sessionData['meter_number'] ?? null;
            $amount = $amount ?? $sessionData['amount'] ?? null;
            $provider = $provider ?? $sessionData['provider'] ?? null;
        }
        
        if (!$meterNumber && !$amount && !$provider) {
            return $this->getElectricityHelpMessage($hasValidSession);
        }
        
        if ($meterNumber && !$amount && !$provider) {
            // Update session with meter number
            $session = $this->getOrCreateSession($user, 'electricity');
            $this->updateSessionData($session, ['meter_number' => $meterNumber]);
            
            return $this->getElectricityAmountRequest($meterNumber, false);
        }
        
        if ($meterNumber && $amount && !$provider) {
            // Update session with amount
            $session = $this->getOrCreateSession($user, 'electricity');
            $this->updateSessionData($session, [
                'meter_number' => $meterNumber,
                'amount' => $amount
            ]);
            
            return $this->getElectricityProviderRequest($meterNumber, $amount, false);
        }
        
        if ($meterNumber && $amount && $provider) {
            // Clear session after purchase
            WhatsappSession::where('user_id', $user->id)
                ->where('context', 'electricity')
                ->delete();
            
            return app(ElectricityController::class)
                ->purchase($user, $meterNumber, $amount, $provider);
        }
        
        return $this->getElectricityHelpMessage($hasValidSession);
    }

    /**
     * Get helpful electricity instructions message
     */
    private function getElectricityHelpMessage($hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "⚡ To pay your electricity bill, you can say it naturally:\n\n" .
               "Examples:\n" .
               "• *Pay light bill 1234567890 5000 eko*\n" .
               "• *Electric 1234567890 5000 portharcourt*\n\n" .
               "Or simply say: *electricity* and I'll guide you step by step! ⚡" .
               $sessionNotice;
    }

    /**
     * Get request for electricity amount
     */
    private function getElectricityAmountRequest($meterNumber, $hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "🎯 Meter number: *{$meterNumber}*\n\n" .
               "💰 How much do you want to pay?\n\n" .
               "Examples:\n" .
               "• *Electric 5000*\n" .
               "• *Electric 2000*" .
               $sessionNotice;
    }

    /**
     * Get request for electricity provider
     */
    private function getElectricityProviderRequest($meterNumber, $amount, $hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "💰 Payment: *₦" . number_format($amount) . "* for meter *{$meterNumber}*\n\n" .
               "📍 Which electricity provider?\n\n" .
               "Examples:\n" .
               "• *Electric eko*\n" .
               "• *Electric ikeja*\n" .
               "• *Electric ibadan*\n" .
               "• *Electric abuja*\n" .
               "• *Electric kano*\n" .
               "• *Electric jos*\n" .
               "• *Electric kaduna*\n" .
               "• *Electric portharcourt*" .
               "• *Electric aba*" .
               "• *Electric enugu*" .
               "• *Electric benin*" .
               "• *Electric yola*" .
               $sessionNotice;
    }

    /**
     * ENHANCED: Handle transfer with conversational flow and session timeout
     */
    private function handleTransferFlow($user, $message, $entities)
    {
        $amount = $entities['amount'];
        $recipient = $entities['recipient'];
        
        // Check for active confirmation session
        $confirmSession = WhatsappSession::where('user_id', $user->id)
            ->where('context', 'transfer_confirm')
            ->latest()
            ->first();
        
        // Handle confirmation
        if ($confirmSession && $this->isSessionValid($confirmSession) && 
            preg_match('/\b(confirm|yes|proceed|ok)\b/i', $message)) {
            return $this->processTransferConfirmation($user, $confirmSession);
        }
        
        // Check for regular transfer session
        $session = WhatsappSession::where('user_id', $user->id)
            ->where('context', 'transfer')
            ->latest()
            ->first();
        
        $sessionData = [];
        $hasValidSession = false;
        
        if ($session && $this->isSessionValid($session)) {
            $sessionData = json_decode($session->data ?? '{}', true) ?? [];
            $hasValidSession = true;
            
            // Merge with entities
            $amount = $amount ?? $sessionData['amount'] ?? null;
            $recipient = $recipient ?? $sessionData['recipient'] ?? null;
        }
        
        if (!$amount && !$recipient) {
            return $this->getTransferHelpMessage($hasValidSession);
        }
        
        if ($amount && !$recipient) {
            // Update session with amount
            $session = $this->getOrCreateSession($user, 'transfer');
            $this->updateSessionData($session, ['amount' => $amount]);
            
            return $this->getTransferRecipientRequest($amount, false);
        }
        
        if ($recipient && !$amount) {
            $recipientUser = $this->transferController->findRecipient($recipient);
            
            if (!$recipientUser) {
                return "⚠️ Recipient not found.\n\n" .
                       "❌ *{$recipient}* is not on A-Pay.\n\n" .
                       "Please check and try again.";
            }
            
            // Update session with recipient
            $session = $this->getOrCreateSession($user, 'transfer');
            $this->updateSessionData($session, [
                'recipient' => $recipient,
                'recipient_user_id' => $recipientUser->id
            ]);
            
            return $this->getTransferAmountRequest($recipientUser, false);
        }
        
        if ($amount && $recipient) {
            if ($amount < 50) {
                return "⚠️ Minimum transfer amount is ₦50.00\n\n" .
                       "Please enter an amount of ₦50 or more.";
            }
            
            $recipientUser = $this->transferController->findRecipient($recipient);
            
            if (!$recipientUser) {
                return "⚠️ Recipient not found.\n\n" .
                       "❌ *{$recipient}* is not on A-Pay.\n\n" .
                       "Please check and try again.";
            }
            
            return $this->createTransferConfirmation($user, $amount, $recipient, $recipientUser);
        }
        
        return $this->getTransferHelpMessage($hasValidSession);
    }

    /**
     * Get helpful transfer instructions message
     */
    private function getTransferHelpMessage($hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "💸 To transfer money, you can say it naturally:\n\n" .
               "Examples:\n" .
               "• *I want to send 5000 to 09079916807*\n" .
               "• *Transfer 3000 to account 1234567890*\n" .
               "• *Send 2000 naira to 09079916807*\n" .
               "• *Pay 5000 to 09079916807*\n" .
               "• *Transfer 5000 09079916807*\n\n" .
               "Or simply say: *transfer* and I'll guide you step by step! 💚" .
               $sessionNotice;
    }

    /**
     * Get request for transfer recipient
     */
    private function getTransferRecipientRequest($amount, $hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        
        return "💰 You want to send *₦" . number_format($amount, 2) . "*\n\n" .
               "📱 Who should receive this money?\n\n" .
               "Examples:\n" .
               "• *09079916807*\n" .
               "• *To account 1234567890*\n" .
               "• *Send to 08012345678*" .
               $sessionNotice;
    }

    /**
     * Get request for transfer amount
     */
    private function getTransferAmountRequest($recipientUser, $hasValidSession = false)
    {
        $sessionNotice = $hasValidSession ? "" : "\n\n⏱️ *Session active! Please respond within 1 minute.*";
        $recipientName = $recipientUser->name ?? 'A-Pay User';
        
        return "👤 Sending to: *{$recipientName}*\n" .
               "📱 {$recipientUser->mobile}\n\n" .
               "💰 How much would you like to send?\n\n" .
               "Examples:\n" .
               "• *5000*\n" .
               "• *Send 3000*\n" .
               "• *Amount 2000*" .
               $sessionNotice;
    }

    /**
     * Create transfer confirmation session
     */
    private function createTransferConfirmation($user, $amount, $recipient, $recipientUser)
    {
        $session = WhatsappSession::firstOrNew([
            'user_id' => $user->id,
            'context' => 'transfer_confirm'
        ]);
        
        $session->id = $session->id ?? Str::uuid();
        $session->data = json_encode([
            'amount' => $amount,
            'recipient' => $recipient,
            'recipient_id' => $recipientUser->id
        ]);
        $session->save();

        $recipientName = $recipientUser->name ?? 'A-Pay User';
        return "⚠️ *CONFIRM TRANSFER*\n\n" .
               "Sending:\n" .
               "💰 Amount: *₦" . number_format($amount, 2) . "*\n\n" .
               "To:\n" .
               "👤 Name: *{$recipientName}*\n" .
               "📱 Phone: {$recipientUser->mobile}\n" .
               "🔢 Account: {$recipientUser->account_number}\n\n" .
               "Reply:\n" .
               "• *confirm* or *yes* to proceed\n" .
               "• *cancel* to abort\n\n" .
               "⏱️ *Please confirm within 1 minute.*";
    }

    /**
     * Process transfer confirmation
     */
    private function processTransferConfirmation($user, $confirmSession)
    {
        $sessionData = json_decode($confirmSession->data ?? '{}', true) ?? [];
        $amount = $sessionData['amount'] ?? null;
        $recipient = $sessionData['recipient'] ?? null;
        
        if (!$amount || !$recipient) {
            $confirmSession->delete();
            return "⚠️ Session expired. Start a new transfer.";
        }
        
        $confirmSession->delete();
        
        $result = $this->transferController->transfer($user, $recipient, $amount);
        
        if ($result['success']) {
            $creditAlertMsg = $this->transferController->sendCreditAlert(
                $result['recipient'],
                $user,
                $amount,
                $result['reference'],
                $result['recipient_balance']
            );
            
            $this->sendMessage($result['recipient']->mobile, $creditAlertMsg);
            
            return $result['message'];
        } else {
            return $result['message'];
        }
    }

    /**
     * Handle transactions
     */
    private function handleTransactions($user)
    {
        // Fetch all successful transactions for current year
        $transactions = $user->transactions()
            ->where('status', 'SUCCESS')
            ->whereYear('created_at', now()->year)
            ->latest()
            ->get();

        if ($transactions->isEmpty()) {
            return "🧾 No recent transactions found.";
        }

        // Initialize Counters
        $currentMonth = now()->month;
        
        $stats = [
            'month_total' => 0,
            'month_count' => 0,
            'year_debit' => 0,     // New
            'year_credit' => 0,     // New
            'airtime_total' => 0,
            'data_total' => 0,
            'electricity_total' => 0,
            'month_credit' => 0,
            'month_debit' => 0,
            'month_cashback' => 0,
            'month_topup' => 0,
        ];

        // Loop and Calculate Stats
        foreach ($transactions as $t) {
            $desc = strtoupper($t->description ?? '');
            $amount = (float) $t->amount;
            $type = strtoupper($t->type ?? '');

            // 1. YEARLY LOGIC (Updated)
            if ($type == 'DEBIT' || str_contains($desc, 'PURCHASE')) {
                $stats['year_debit'] += $amount; // Track Yearly Debit

                // Category Breakdown
                if (str_contains($desc, 'AIRTIME PURCHASE')) {
                    $stats['airtime_total'] += $amount;
                } elseif (str_contains($desc, 'DATA PURCHASE') || str_contains($desc, 'DATA SUBSCRIPTION')) {
                    $stats['data_total'] += $amount;
                } elseif (str_contains($desc, 'ELECTRICITY')) {
                    $stats['electricity_total'] += $amount;
                }
            }

            // Track Yearly Credit
            if ($type == 'CREDIT') {
                $stats['year_credit'] += $amount;
            }

            // 2. MONTHLY LOGIC
            if ($t->created_at->month == $currentMonth) {
                
                // Credit vs Debit Totals
                if ($type == 'CREDIT') {
                    $stats['month_credit'] += $amount;
                } elseif ($type == 'DEBIT') {
                    $stats['month_debit'] += $amount;
                    $stats['month_total'] += $amount; 
                    $stats['month_count']++;
                }

                // Wallet Top-up
                if ($type == 'CREDIT' && str_contains($desc, 'WALLET TOP-UP')) {
                    $stats['month_topup'] += $amount;
                }

                // Cashback
                if (str_contains($desc, 'CASHBACK')) {
                    $stats['month_cashback'] += $amount;
                }
            }
        
        }

        // Generate Image (Receipt)
        try {
            $receiptGenerator = app(\App\Services\ReceiptGenerator::class);
            $imageUrl = $receiptGenerator->generateHistoryReport($stats);
        } catch (\Exception $e) {
            // Fallback if image generation fails
            $imageUrl = null;
        }

        // Format "Recent Transactions" text for the Caption
        $recent = $transactions->take(5); 
        $caption = "🧾 *Recent Transactions (5 shown below):*\n\n";
        foreach ($recent as $t) {
            $caption .= "• Beneficiary: {$t->beneficiary}\n";
            $caption .= "  Amount: ₦{$t->amount}\n";
            $caption .= "  Type: {$t->type}\n"; // Added Type
            $caption .= "  Description: {$t->description}\n";
            $caption .= "  Date: {$t->created_at->format('d M Y')}\n\n";
        }
        $caption .= "_To get your full transaction history, please reach out to customer support at 👉 *+234-803-590-6313* to generate your account statement_";

        // Return Image + Caption
        return [
            'type' => 'image',
            'receipt_url' => $imageUrl,
            'message' => $caption
        ];
    }

    /**
     * Handle support
     */
    private function handleSupport()
    {
        return "💚 *A-Pay Support Team*\n\nIf you need assistance, please contact our support via WhatsApp:\n👉 *+234-803-590-6313*\n\nWe're available to help you resolve any issue as quickly as possible.\n\nIf you'd like to return to the *main menu*, simply type:\n➡️ *menu*";
    }

    /**
     * Handle thank you
     */
    private function handleThankYou()
    {
        return "💚 You're welcome! 😊\n\n" .
               "If you'd like to return to the main menu, just type:\n➡️ *menu*";
    }

    /**
     * Handle about founder
     */
    private function handleAboutFounder()
    {
        return "💚 Joshua Adeyemi is the founder and CEO of *A-Pay*, a Nigerian software engineer based in Lagos. He builds solutions that solve real-world problems.\n\n" .
               "If you'd like to return to the main menu, type:\n➡️ *menu*";
    }

    /**
     * Handle about company
     */
    private function handleAboutCompany()
    {
        return "💚 *A-Pay* operates under AfricGEM International Company Limited, a fully registered company in Nigeria under CAC.\n\n" .
               "Registration Number: 8088462\n\n" .
               "If you'd like to return to the main menu, type:\n➡️ *menu*";
    }

    /**
     * Handle about A-Pay
     */
    private function handleAboutApay()
    {
        return "💚 *A-Pay* is a seamless platform that helps you:\n" .
               "- Buy Airtime\n- Buy Data\n- Pay Electricity Bills\n- Fund your wallet and track transactions easily.\n\n" .
               "All services are accessible via WhatsApp and our website.\n\n" .
               "Type *menu* to return to the main menu.";
    }

    /**
     * Handle unknown intent
     */
    private function handleUnknown()
    {
        return "❓ Sorry, I didn't understand that.\n\nType *menu* to see available options.";
    }

    /**
     * Main menu
     */
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
               "We're always ready to help you with any issue.\n\n" .
               "*Example: airtime 500 08012345678*";
    }

    /**
     * Update session data
     */
    private function updateSessionData($session, $data)
    {
        $sessionData = json_decode($session->data ?? '{}', true) ?? [];
        $mergedData = array_merge($sessionData, $data);
        $session->data = json_encode($mergedData);
        $session->save();
    }

    /**
     * Send message via Twilio
     */ 
     public function sendMessage($to, $body)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = 'whatsapp:' . env('TWILIO_W_NUMBER');
        
        if (!$sid || !$token || !$from) {
            \Log::error('Missing Twilio credentials');
            return;
        }

        // Check if body is an array of messages (multiple messages)
        if (is_array($body) && isset($body[0]) && is_array($body[0])) {
            // Multiple messages - send each one
            foreach ($body as $message) {
                $this->sendSingleMessage($to, $message, $sid, $token, $from);
                // small delay between messages so they arrive in order
                usleep(500000); // 0.5 second delay
            }
            return;
        }

        // Single message - send it
        $this->sendSingleMessage($to, $body, $sid, $token, $from);
    }

    private function sendSingleMessage($to, $body, $sid, $token, $from)
    {
        try {
            $client = new Client($sid, $token);
            
            // Check if body is an array with image receipt
            if (is_array($body)) {
                if ($body['type'] === 'image' && isset($body['receipt_url'])) {
                    // Send message with image
                    $message = $client->messages->create("whatsapp:$to", [
                        'from' => $from,
                        'body' => $body['message'],
                        'mediaUrl' => [$body['receipt_url']]
                    ]);
                } else {
                    // Send text only
                    $message = $client->messages->create("whatsapp:$to", [
                        'from' => $from,
                        'body' => $body['message']
                    ]);
                }
                
                // Log for structured message
                WhatsappMessage::create([
                    'phone_number' => $to,
                    'direction' => 'outgoing',
                    'message_body' => $body['message'],
                    'message_sid' => $message->sid,
                    'status' => $message->status,
                    'metadata' => [
                        'from' => $from,
                        'sent_at' => now()->toIso8601String(),
                        'has_media' => $body['type'] === 'image'
                    ]
                ]);
            } else {
                // Legacy text message
                $message = $client->messages->create("whatsapp:$to", [
                    'from' => $from,
                    'body' => $body
                ]);
                
                // Log for legacy text message
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
            }
        } catch (\Exception $e) {
            // Log failed message
            WhatsappMessage::create([
                'phone_number' => $to,
                'direction' => 'outgoing',
                'message_body' => is_array($body) ? $body['message'] : $body,
                'status' => 'failed',
                'metadata' => [
                    'error' => $e->getMessage(),
                    'attempted_at' => now()->toIso8601String()
                ]
            ]);
            
            \Log::error('Failed to send message', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
        }
    }
}