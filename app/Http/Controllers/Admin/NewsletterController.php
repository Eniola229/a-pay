<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Balance;
use Twilio\Rest\Client;
use Cloudinary\Cloudinary;
use Carbon\Carbon;

class NewsletterController extends Controller
{
    protected $cloudinary;

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
    }

    public function index()
    {
        $eligibleUsers = User::where('created_at', '>=', '2025-12-01')->count();
        
        $lowBalanceUsers = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function($query) {
                $query->where('balance', '<', 100);
            })->count();

        return view('admin.newsletter', compact('eligibleUsers', 'lowBalanceUsers'));
    }

    public function sendNewsletter(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'media'   => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:16000'
        ]);

        $mediaUrl = null;

        if ($request->hasFile('media')) {
            $file    = $request->file('media');
            $isVideo = $file->getMimeType() === 'video/mp4';

            $uploaded = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                [
                    'resource_type' => $isVideo ? 'video' : 'image',
                    'folder'        => 'newsletter-media',
                ]
            );

            $mediaUrl = $uploaded['secure_url'];
        }

        $users  = User::where('created_at', '>=', '2025-12-01')->get();
        $sent   = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendMessage($user->mobile, $request->message, $mediaUrl);
                $sent++;
            } catch (\Exception $e) {
                \Log::error('Newsletter send failed', [
                    'user'  => $user->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return back()->with('success', "Newsletter sent! ✅ Sent: {$sent} | Failed: {$failed}");
    }

    public function sendLowBalanceAlert()
    {
        $users = User::where('created_at', '>=', '2025-12-01')
            ->whereHas('balance', function($query) {
                $query->where('balance', '<', 100);
            })
            ->with('balance')
            ->get();

        $sent    = 0;
        $failed  = 0;
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
                    'user'  => $user->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return back()->with('success', "Low balance alerts sent! ✅ Sent: {$sent} | Failed: {$failed}");
    }

    private function sendMessage($to, $body, $mediaUrl = null)
    {
        $sid   = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from  = 'whatsapp:' . env('TWILIO_W_NUMBER');
        
        if (!$sid || !$token || !$from) {
            \Log::error('Missing Twilio credentials', [
                'sid'   => $sid,
                'token' => $token,
                'from'  => $from,
            ]);
            return;
        }
        
        $client = new Client($sid, $token);

        $params = [
            'from' => $from,
            'body' => $body,
        ];

        if ($mediaUrl) {
            $params['mediaUrl'] = [$mediaUrl];
        }

        $client->messages->create("whatsapp:$to", $params);
    }
}