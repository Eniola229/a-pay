<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Balance;
use App\Models\WhatsappSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    // Method now accepts WhatsappController as parameter
    public function handleRegistration($from, $message, $whatsappController)
    {
        // Get or create session
        $session = WhatsappSession::firstOrCreate(
            ['phone' => $from, 'context' => 'register'],
            ['data' => json_encode([])]
        );

        $sessionData = json_decode($session->data ?? '{}', true);

        // Parse name and email from message
        $parsed = $this->parseNameAndEmail($message, $sessionData);
        $name = $parsed['name'];
        $email = $parsed['email'];

        // Update session
        $session->data = json_encode([
            'name'  => $name,
            'email' => $email,
            'phone' => $from
        ]);
        $session->save();

        // Validate we have all required data
        if (!$name || !$email) {
            return $this->sendWelcomePrompt($from, $whatsappController);
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            $session->delete();
            return $this->sendEmailExistsError($from, $email, $whatsappController);
        }

        // Create user with Paystack integration
        try {
            $user = $this->createUserWithPaystack($from, $name, $email);
            $session->delete();

            // Send success messages
            $this->sendWelcomeMessage($from, $name, $whatsappController);
            
            return $this->sendFundingDetails(
                $from,
                $user->account_number,
                $user->account_name ?? 'A-Pay Account',
                $user->bank_name ?? 'Wema Bank',
                $whatsappController
            );

        } catch (\Exception $e) {
            $session->delete();
            Log::error('Registration failed', ['error' => $e->getMessage()]);
            return $this->sendRegistrationError($from, $e->getMessage(), $whatsappController);
        }
    }

    protected function parseNameAndEmail($message, $sessionData)
    {
        if (preg_match('/([a-zA-Z ]+)\s+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,})/i', $message, $matches)) {
            return [
                'name'  => trim($matches[1]),
                'email' => trim($matches[2])
            ];
        }

        return [
            'name'  => $sessionData['name']  ?? null,
            'email' => $sessionData['email'] ?? null
        ];
    }

    protected function createUserWithPaystack($phone, $name, $email)
    {
        $parts = explode(' ', trim($name), 2);
        $firstName = $parts[0];
        $lastName  = $parts[1] ?? $parts[0];

        DB::beginTransaction();
        try {
            // Get or create Paystack customer
            $customerCode = $this->getOrCreatePaystackCustomer($email, $firstName, $lastName, $phone);

            // Create virtual account
            $vaData = $this->createVirtualAccount($customerCode);

            // Create local user
            $user = User::create([
                'name'           => ucwords(strtolower($name)),
                'mobile'         => $phone,
                'email'          => $email,
                'password'       => '',
                'account_number' => $vaData['account_number'],
            ]);

            Balance::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            DB::commit();
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getOrCreatePaystackCustomer($email, $firstName, $lastName, $phone)
    {
        // Check if customer exists
        $customerLookup = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
        ])->get("https://api.paystack.co/customer/{$email}");

        $lookupData = $customerLookup->json();

        if (isset($lookupData['data']['customer_code'])) {
            // Update existing customer
            $customerCode = $lookupData['data']['customer_code'];
            
            $updateCustomer = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
            ])->put("https://api.paystack.co/customer/{$customerCode}", [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'phone'      => $phone,
            ]);

            $updateData = $updateCustomer->json();
            if (!($updateData['status'] ?? false)) {
                Log::error('Paystack update customer failed', $updateData);
                throw new \Exception('Failed to update Paystack customer.');
            }

            return $customerCode;
        }

        // Create new customer
        $createCustomer = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
        ])->post('https://api.paystack.co/customer', [
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
        ]);

        $customerData = $createCustomer->json();

        if (!($customerData['status'] ?? false) || !isset($customerData['data']['customer_code'])) {
            Log::error('Paystack customer creation failed', $customerData);
            throw new \Exception('Failed to create Paystack customer.');
        }

        return $customerData['data']['customer_code'];
    }

    protected function createVirtualAccount($customerCode)
    {
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

        $accountNumber = $vaData['data']['account_number'];
        $bankName      = $vaData['data']['bank']['name'] ?? null;
        $accountName   = $vaData['data']['account_name'] ?? null;

        if (!$accountNumber || !$bankName || !$accountName) {
            Log::error('Paystack VA missing required fields', $vaData);
            throw new \Exception('Incomplete Virtual Account info.');
        }

        return [
            'account_number' => $accountNumber,
            'bank_name'      => $bankName,
            'account_name'   => $accountName,
        ];
    }

    // Pass WhatsappController as parameter
    protected function sendWelcomePrompt($to, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "👋 Welcome to *A-Pay!* \n\nTo create an account, reply with your _Name_ and _Email_ like this:\n\n*John Doe john@gmail.com*"
        );
    }

    protected function sendEmailExistsError($to, $email, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "⚠️ The email *{$email}* already exists.\n\nUse the same phone number you registered with."
        );
    }

    protected function sendRegistrationError($to, $error, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "❌ Registration failed: {$error}\nPlease try again."
        );
    }

    protected function sendWelcomeMessage($to, $name, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "🎉 *Congratulations {$name}!* 🎉\n\n".
            "Your A-Pay account has been created successfully! 🎊\n\n".
            "You can now buy:\n".
            "💵 Airtime\n📶 Data\n💡 Bills\n⚡ Utilities & more\n\n".
            "Type *menu* to see available services.\n\n".
            "__🔐 For security, enable WhatsApp Lock.__"
        );
    }

    protected function sendFundingDetails($to, $accountNumber, $accountName, $bankName, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "💰 *TO FUND YOUR A-PAY WALLET*\n\n".
            "🏦 *Bank:* {$bankName}\n".
            "👤 *Account Name:* {$accountName}\n".
            "🔢 *Account Number:* {$accountNumber}\n\n".
            "Transfer to the account above to top-up instantly.\n\n".
            "__Kindly PIN this message for easy access.__"
        );
    }
}