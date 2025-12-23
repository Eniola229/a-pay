<?php

namespace App\Http\Controllers\WebhookControllers;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserValidationController extends Controller
{
    // Method now accepts WhatsappController as parameter
    public function validate(User $user, $from, $whatsappController)
    {
        // Check if user is blocked
        if ($user->is_status === 'BLOCKED') {
            return $this->sendBlockedMessage($from, $whatsappController);
        }

        // Check KYC requirements
        $balance = $user->balance()->first();
        if ($balance && $balance->balance >= 100000) {
            return $this->checkKycRequirements($user, $from, $whatsappController);
        }

        return null; // All validations passed
    }

    protected function checkKycRequirements(User $user, $from, $whatsappController)
    {
        // User doesn't have KYC
        if (!$user->hasKyc()) {
            $kycUrl = route('kyc.form', ['user' => $user->id, 'token' => encrypt($user->id)]);
            return $this->sendKycRequiredMessage($from, $kycUrl, $whatsappController);
        }

        $kyc = $user->kycProfile;

        // KYC is rejected
        if ($kyc && $kyc->isRejected()) {
            return $this->sendKycRejectedMessage($from, $kyc->rejection_reason, $whatsappController);
        }

        // KYC is pending
        if ($kyc && $kyc->isPending()) {
            return $this->sendKycPendingMessage($from, $whatsappController);
        }

        return null; // KYC is approved
    }

    // Pass WhatsappController as parameter
    protected function sendBlockedMessage($to, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "*⚠️ Your A-Pay account has been BLOCKED! 🔒* \n\n Please reach out to Customer Support on WhatsApp 📲 09079916807 to get it restored."
        );
    }

    protected function sendKycRequiredMessage($to, $kycUrl, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "⚠️ *KYC VERIFICATION REQUIRED* ⚠️\n\n".
            "Your account balance has reached ₦100,000 or more.\n\n".
            "To continue using A-Pay services, please complete your KYC verification:\n\n".
            "🔗 {$kycUrl}\n\n".
            "📋 *Required Documents:*\n".
            "• Passport Photo 📸\n".
            "• BVN Number 🆔\n".
            "• NIN Number 🆔\n".
            "• Proof of Address 📄\n\n".
            "⏱️ This takes only 5 minutes!"
        );
    }

    protected function sendKycRejectedMessage($to, $reason, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "❌ *KYC VERIFICATION REJECTED* ❌\n\n".
            "📝 *Reason:* {$reason}\n\n".
            "Please contact support on WhatsApp 📲 09079916807 to resolve this issue."
        );
    }

    protected function sendKycPendingMessage($to, $whatsappController)
    {
        return $whatsappController->sendMessage(
            $to,
            "⏳ *KYC VERIFICATION PENDING* ⏳\n\n".
            "Your KYC documents are currently under review.\n\n".
            "You'll be notified once the verification is complete.\n\n".
            "⚠️ *Note:* If any errors are found, your account may be suspended until corrections are made.\n\n".
            "For urgent queries, contact support 📲 09079916807"
        );
    }
}