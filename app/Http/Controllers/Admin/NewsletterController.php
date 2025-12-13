<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Balance;
use Twilio\Rest\Client;
use Carbon\Carbon;

class NewsletterController extends Controller
{
    public function index()
    {
        // Count eligible users (registered from Dec 1, 2025)
        $eligibleUsers = User::where('created_at', '>=', '2025-12-01')->count();
        
        // Count low balance users
        $lowBalanceUsers = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function($query) {
                $query->where('balance', '<', 100);
            })->count();

        return view('admin.newsletter', compact('eligibleUsers', 'lowBalanceUsers'));
    }

    public function sendNewsletter(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        // Get users registered from December 1, 2025
        $users = User::where('created_at', '>=', '2025-12-01')->get();

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendMessage($user->mobile, $request->message);
                $sent++;
            } catch (\Exception $e) {
                \Log::error('Newsletter send failed', [
                    'user' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return back()->with('success', "Newsletter sent! ✅ Sent: {$sent} | Failed: {$failed}");
    }

    public function sendLowBalanceAlert()
    {
        // Get users with balance less than ₦100, registered from Dec 1, 2025
        $users = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function($query) {
                $query->where('balance', '<', 100);
            })
            ->with('balance')
            ->get();

        $sent = 0;
        $failed = 0;

        $message = "💚 *A-Pay Balance Alert* 💚\n\n" .
                   "Hi! Your A-Pay wallet balance is low.\n\n" .
                   "Don't get caught off guard when you need to buy airtime, data, or pay bills! Top up now so you're always ready for instant purchases. 📱💡\n\n" .
                   "Fund your wallet and keep transacting smoothly!\n\n" .
                   "Thank you for using A-Pay 💚";

        foreach ($users as $user) {
            try {
                $this->sendMessage($user->mobile, $message);
                $sent++;
            } catch (\Exception $e) {
                \Log::error('Low balance alert failed', [
                    'user' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return back()->with('success', "Low balance alerts sent! ✅ Sent: {$sent} | Failed: {$failed}");
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
}