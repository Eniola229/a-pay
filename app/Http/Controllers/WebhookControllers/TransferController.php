<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferController extends Controller
{
    /**
     * Find recipient by phone number or account number
     * 
     * @param string $identifier Phone number or account number
     * @return User|null
     */
    public function findRecipient($identifier)
    {
        // Clean the identifier
        $identifier = trim($identifier);
        
        // Check if it's an account number (10 digits, no +234 prefix)
        if (preg_match('/^\d{10}$/', $identifier) && !preg_match('/^0\d{9}$/', $identifier)) {
            return User::where('account_number', $identifier)->first();
        }
        
        // It's a phone number - normalize it
        $normalizedPhone = $this->normalizePhoneNumber($identifier);
        
        if (!$normalizedPhone) {
            return null;
        }
        
        // Search by mobile
        return User::where('mobile', $normalizedPhone)->first();
    }

    /**
     * Normalize phone number to +234 format
     * 
     * @param string $phone
     * @return string|null
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove spaces and special characters
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If starts with +234, return as is
        if (preg_match('/^\+234\d{10}$/', $phone)) {
            return $phone;
        }
        
        // If starts with 234, add +
        if (preg_match('/^234\d{10}$/', $phone)) {
            return '+' . $phone;
        }
        
        // If starts with 0, replace with +234
        if (preg_match('/^0\d{10}$/', $phone)) {
            return '+234' . substr($phone, 1);
        }
        
        return null;
    }

    /**
     * Process transfer between A-Pay accounts
     * 
     * @param User $sender
     * @param string $recipientIdentifier
     * @param float $amount
     * @return array
     */
    public function transfer($sender, $recipientIdentifier, $amount)
    {
        // Validate amount
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => "⚠️ Invalid amount. Please enter a valid amount greater than ₦0."
            ];
        }

        // Find recipient
        $recipient = $this->findRecipient($recipientIdentifier);
        
        if (!$recipient) {
            return [
                'success' => false,
                'message' => "⚠️ Recipient not found.\n\n❌ *{$recipientIdentifier}* is not registered on A-Pay.\n\nPlease check the phone number or account number and try again."
            ];
        }

        // Check if trying to send to self
        if ($sender->id === $recipient->id) {
            return [
                'success' => false,
                'message' => "⚠️ You cannot transfer to yourself.\n\nPlease enter a different recipient."
            ];
        }

        // Check sender balance
        $senderBalance = Balance::where('user_id', $sender->id)->first();
        
        if (!$senderBalance || $senderBalance->balance < $amount) {
            $shortBy = $amount - ($senderBalance->balance ?? 0);
            return [
                'success' => false,
                'message' => "😔 Oops! Insufficient balance.\n\n💰 Your wallet: ₦" . number_format($senderBalance->balance ?? 0, 2) . "\n💸 Transfer amount: ₦" . number_format($amount, 2) . "\n🔴 Short by: ₦" . number_format($shortBy, 2) . "\n\nPlease fund your wallet and try again! 💳"
            ];
        }

        // Process transfer in transaction
        DB::beginTransaction();
        
        try {
            // Deduct from sender
            $senderBalance->decrement('balance', $amount);
            $newSenderBalance = $senderBalance->fresh()->balance;

            // Credit recipient
            $recipientBalance = Balance::firstOrCreate(
                ['user_id' => $recipient->id],
                ['balance' => 0]
            );
            $recipientBalance->increment('balance', $amount);
            $newRecipientBalance = $recipientBalance->fresh()->balance;

            // Generate reference
            $reference = 'A-PAY_' . strtoupper(uniqid());

            // Create transaction records
            // Debit transaction for sender
            $senderTransaction = Transaction::create([
                'user_id' => $sender->id,
                'amount' => $amount,
                'type' => 'DEBIT',
                'beneficiary' => $recipient->name ?? $recipient->mobile,
                'description' => "Transfer to " . ($recipient->name ?? $recipient->mobile),
                'reference' => $reference,
                'status' => 'SUCCESS'
            ]);

            // Credit transaction for recipient
            $recipientTransaction = Transaction::create([
                'user_id' => $recipient->id,
                'amount' => $amount,
                'type' => 'CREDIT',
                'beneficiary' => $sender->name ?? $sender->mobile,
                'description' => "Transfer from " . ($sender->name ?? $sender->mobile),
                'reference' => $reference,
                'status' => 'SUCCESS'
            ]);

            DB::commit();

            return [
                'success' => true,
                'sender_balance' => $newSenderBalance,
                'recipient_balance' => $newRecipientBalance,
                'reference' => $reference,
                'recipient' => $recipient,
                'message' => $this->generateSuccessMessage($sender, $recipient, $amount, $reference, $newSenderBalance)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => "❌ Transfer failed due to a system error.\n\nPlease try again later or contact support."
            ];
        }
    }

    /**
     * Generate success message for sender
     * 
     * @param User $sender
     * @param User $recipient
     * @param float $amount
     * @param string $reference
     * @param float $newBalance
     * @return string
     */
    private function generateSuccessMessage($sender, $recipient, $amount, $reference, $newBalance)
    {
        $recipientName = $recipient->name ?? 'A-Pay User';
        $recipientPhone = $recipient->mobile;
        
        return 
            "✅ *TRANSFER SUCCESSFUL*\n\n" .
            "You sent ₦" . number_format($amount, 2) . " to:\n" .
            "👤 *{$recipientName}*\n" .
            "📱 {$recipientPhone}\n\n" .
            "💳 Ref: {$reference}\n" .
            "💰 New Balance: ₦" . number_format($newBalance, 2) . "\n\n" .
            "Thank you for using A-Pay 💚";
    }

    /**
     * Send credit alert to recipient via WhatsApp
     * 
     * @param User $recipient
     * @param User $sender
     * @param float $amount
     * @param string $reference
     * @param float $newBalance
     * @return string
     */
    public function sendCreditAlert($recipient, $sender, $amount, $reference, $newBalance)
    {
        $senderName = $sender->name ?? 'A-Pay User';
        
        $msg = 
            "💳 *CREDIT ALERT*\n\n" .
            "Your A-Pay wallet has been credited.\n\n" .
            "👤 From: *{$senderName}*\n" .
            "💰 Amount: ₦" . number_format($amount, 2) . "\n" .
            "💳 Ref: {$reference}\n" .
            "💵 New Balance: ₦" . number_format($newBalance, 2) . "\n\n" .
            "Thank you for using A-Pay 💚";
        
        // Return the message to be sent by the calling controller
        return $msg;
    }

    /**
     * Format identifier display (hide middle digits for privacy)
     * 
     * @param string $identifier
     * @return string
     */
    private function formatIdentifierDisplay($identifier)
    {
        $identifier = trim($identifier);
        
        // If it's a phone number
        if (preg_match('/^\+?\d+$/', $identifier)) {
            $phone = $this->normalizePhoneNumber($identifier);
            if ($phone && strlen($phone) > 8) {
                return substr($phone, 0, 7) . '***' . substr($phone, -2);
            }
        }
        
        // If it's account number
        if (strlen($identifier) == 10) {
            return substr($identifier, 0, 3) . '****' . substr($identifier, -3);
        }
        
        return $identifier;
    }
}