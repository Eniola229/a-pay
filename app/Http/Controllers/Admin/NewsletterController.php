<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Balance;
use App\Models\WhatsappMessage;
use Twilio\Rest\Client;
use Cloudinary\Cloudinary;
use Carbon\Carbon;

class NewsletterController extends Controller
{
    protected $cloudinary;
    protected $twilioClient;
    protected $twilioFrom;

    public function __construct()
    {
        $cloudinaryUrl = env('CLOUDINARY_URL');

        if ($cloudinaryUrl) {
            $parsed = parse_url($cloudinaryUrl);
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $parsed['host'] ?? env('CLOUDINARY_CLOUD_NAME'),
                    'api_key'    => $parsed['user'] ?? env('CLOUDINARY_API_KEY'),
                    'api_secret' => $parsed['pass'] ?? env('CLOUDINARY_API_SECRET'),
                ],
            ]);
        } else {
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key'    => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
            ]);
        }

        $sid   = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        if ($sid && $token) {
            $this->twilioClient = new Client($sid, $token);
            $this->twilioFrom   = 'whatsapp:' . env('TWILIO_W_NUMBER');
        }
    }

    public function index()
    {
        $eligibleUsers = User::where('created_at', '>=', '2025-12-01')->count();

        $lowBalanceUsers = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function ($query) {
                $query->where('balance', '<', 100);
            })->count();

        return view('admin.newsletter', compact('eligibleUsers', 'lowBalanceUsers'));
    }

    public function sendNewsletter(Request $request)
    {
        $request->validate([
            'message'     => 'required|string|max:1000',
            'template_id' => 'nullable|string|max:255',
            'media'       => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:16000',
        ]);

        $mediaUrl = null;

        if ($request->hasFile('media')) {
            $file    = $request->file('media');
            $isVideo = $file->getMimeType() === 'video/mp4';

            $uploaded = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'resource_type' => $isVideo ? 'video' : 'image',
                    'folder'        => 'apay/newsletter-media',
                ]
            );

            $mediaUrl = $uploaded['secure_url'];
        }

        $users      = User::where('created_at', '>=', '2025-12-01')->get();
        $sent       = 0;
        $failed     = 0;
        $skipped    = 0;
        $templateId = $request->input('template_id');

        foreach ($users as $user) {
            try {
                $hasActiveSession = $this->userHasMessageWithin24Hours($user->mobile);

                if ($hasActiveSession) {
                    // User messaged within 24hrs — send freeform message directly
                    $this->sendMessage($user->mobile, $request->message, $mediaUrl);
                    $sent++;
                } elseif ($templateId) {
                    // Outside 24hr window — send approved template instead
                    $this->sendTemplate($user->mobile, $templateId);
                    $sent++;
                } else {
                    // No active session and no template provided — skip user
                    $skipped++;
                    \Log::info('Newsletter skipped: no active session and no template provided', [
                        'user' => $user->id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Newsletter send failed', [
                    'user'  => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $skipNote = $skipped > 0 ? " | Skipped (outside 24hr window, no template): {$skipped}" : '';
        return back()->with('success', "Newsletter sent! ✅ Sent: {$sent} | Failed: {$failed}{$skipNote}");
    }

    public function sendLowBalanceAlert(Request $request)
    {
        $request->validate([
            'template_id' => 'nullable|string|max:255',
        ]);

        $users = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function ($query) {
                $query->where('balance', '<', 100);
            })
            ->with('balance')
            ->get();

        $sent       = 0;
        $failed     = 0;
        $skipped    = 0;
        $templateId = $request->input('template_id');

        $message = "💚 *A-Pay Balance Alert* 💚\n\n" .
                   "Hi! Your A-Pay wallet balance is low.\n\n" .
                   "Don't get caught off guard when you need to buy airtime, data, or pay bills! " .
                   "Top up now so you're always ready for instant purchases. 📱💡\n\n" .
                   "Fund your wallet and keep transacting smoothly!\n\n" .
                   "Thank you for using A-Pay 💚";

        foreach ($users as $user) {
            try {
                $hasActiveSession = $this->userHasMessageWithin24Hours($user->mobile);

                if ($hasActiveSession) {
                    $this->sendMessage($user->mobile, $message);
                    $sent++;
                } elseif ($templateId) {
                    $this->sendTemplate($user->mobile, $templateId);
                    $sent++;
                } else {
                    $skipped++;
                    \Log::info('Low balance alert skipped: no active session and no template provided', [
                        'user' => $user->id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Low balance alert failed', [
                    'user'  => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $skipNote = $skipped > 0 ? " | Skipped (outside 24hr window, no template): {$skipped}" : '';
        return back()->with('success', "Low balance alerts sent! ✅ Sent: {$sent} | Failed: {$failed}{$skipNote}");
    }

    /**
     * Check if the user sent an inbound message to us within the last 24 hours
     * using the local whatsapp_messages table (direction = 'inbound').
     */
    private function userHasMessageWithin24Hours(string $mobile): bool
    {
        return WhatsappMessage::where('phone_number', $mobile)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->exists();
    }

    /**
     * Send a WhatsApp template message via Twilio Content API.
     * Used for users outside the 24-hour session window.
     */
    private function sendTemplate(string $to, string $templateId): void
    {
        if (!$this->twilioClient) {
            throw new \Exception('Twilio client not initialised — check TWILIO_SID and TWILIO_AUTH_TOKEN in .env');
        }

        $params = [
            'from'       => $this->twilioFrom,
            'contentSid' => $templateId,
        ];

        // Include messaging service SID if configured
        if (env('TWILIO_MESSAGING_SERVICE_SID')) {
            $params['messagingServiceSid'] = env('TWILIO_MESSAGING_SERVICE_SID');
        }

        $this->twilioClient->messages->create("whatsapp:{$to}", $params);
    }

    /**
     * Send a plain freeform WhatsApp message.
     * Only valid within the 24-hour customer service window.
     */
    private function sendMessage(string $to, string $body, ?string $mediaUrl = null): void
    {
        if (!$this->twilioClient) {
            throw new \Exception('Twilio client not initialised — check TWILIO_SID and TWILIO_AUTH_TOKEN in .env');
        }

        $params = [
            'from' => $this->twilioFrom,
            'body' => $body,
        ];

        if ($mediaUrl) {
            $params['mediaUrl'] = [$mediaUrl];
        }

        $this->twilioClient->messages->create("whatsapp:{$to}", $params);
    }
}