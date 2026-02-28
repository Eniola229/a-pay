<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KycProfile;
use App\Models\Logged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class KycController extends Controller
{
    /**
     * Show the KYC form
     */
    public function showForm(Request $request, $user)
    {
        try {
            $userId = decrypt($request->token);

            if ($userId != $user) {
                abort(403, 'Invalid token');
            }

            $user = User::findOrFail($user);

            if ($user->hasKyc()) {
                return view('kyc.already-submitted', compact('user'));
            }

            return view('kyc.form', compact('user'));

        } catch (\Exception $e) {
            abort(403, 'Invalid or expired link');
        }
    }

    /**
     * Validate customer identity via Paystack.
     *
     * Docs: https://paystack.com/docs/identity-verification/validate-customer/
     *
     * How it works:
     *   1. Send POST /customer/:code/identification
     *      :code = the customer's email address OR Paystack customer_code
     *   2. Paystack verifies ASYNCHRONOUSLY — no OTP, no polling
     *   3. Paystack fires a webhook when done:
     *        customeridentification.success  — BVN matched the bank account
     *        customeridentification.failed   — could not verify
     *
     * Required fields:
     *   country        => "NG"
     *   type           => "bank_account"  (only supported type right now)
     *   value          => BVN number
     *   bvn            => BVN number
     *   bank_code      => bank code e.g. "044" for Access Bank
     *   account_number => 10-digit NUBAN linked to that BVN
     *   first_name     => must match BVN record
     *   last_name      => must match BVN record
     */
    public function validateCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required',
            'first_name'     => 'required|string',
            'last_name'      => 'required|string',
            'bvn'            => 'required|digits:11',
            'bank_code'      => 'required|string',
            'account_number' => 'required|digits:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $userId = $request->user_id;
        $user   = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        try {
            $code = urlencode($user->email);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json',
            ])->post("https://api.paystack.co/customer/{$code}/identification", [
                'country'        => 'NG',
                'type'           => 'bank_account',
                'value'          => $request->bvn,
                'bvn'            => $request->bvn,
                'bank_code'      => $request->bank_code,
                'account_number' => $request->account_number,
                'first_name'     => $request->first_name,
                'last_name'      => $request->last_name,
            ]);

            $data = $response->json();

            $logContext = [
                'full_response' => $data,
                'http_status'   => $response->status(),
            ];

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@validateCustomer',
                'for'         => 'Paystack Validate Customer',
                'message'     => 'Sent BVN + bank account to Paystack. Awaiting async webhook result.',
                'type'        => 'info',
                'stack_trace' => json_encode($logContext),
                't_reference' => $data['data']['customer_code'] ?? null,
            ]);

            Log::info('[KYC] validateCustomer — BVN submitted to Paystack', array_merge(
                ['user_id' => $userId],
                $logContext
            ));

            /*
             * Paystack returns HTTP 200/202 + "status": true to confirm
             * the request was accepted. Actual pass/fail comes via webhook.
             */
            if ($response->successful() && ($data['status'] ?? false) === true) {
                session(['kyc_bvn_submitted_' . $userId => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'BVN details submitted. Please complete the rest of the form.',
                ]);
            }

            $failContext = [
                'full_response' => $data,
                'http_status'   => $response->status(),
            ];

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@validateCustomer',
                'for'         => 'Paystack Validate Customer Failed',
                'message'     => $data['message'] ?? 'Paystack rejected the validation request.',
                'type'        => 'error',
                'stack_trace' => json_encode($failContext),
                't_reference' => null,
            ]);

            Log::error('[KYC] validateCustomer — Paystack rejected request', array_merge(
                ['user_id' => $userId, 'message' => $data['message'] ?? 'No message'],
                $failContext
            ));

            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'Could not verify your details. Please check and try again.',
            ], 400);

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@validateCustomer',
                'for'         => 'Validate Customer Exception',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => null,
            ]);

            Log::error('[KYC] validateCustomer — Exception thrown', [
                'user_id'   => $userId,
                'error'     => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service unavailable. Please try again.',
            ], 500);
        }
    }

    /**
     * Paystack Webhook Handler
     *
     * Paystack sends this after async identity verification completes.
     * Events: customeridentification.success | customeridentification.failed
     *
     * IMPORTANT — add this route OUTSIDE the web middleware group (no CSRF):
     *   Route::post('/webhook/paystack', [KycController::class, 'webhook'])->name('webhook.paystack');
     *
     * Then set your webhook URL in:
     *   Paystack Dashboard > Settings > API Keys & Webhooks > Webhook URL
     */
    public function webhook(Request $request)
    {
        $paystackSig = $request->header('x-paystack-signature');
        $expectedSig = hash_hmac('sha512', $request->getContent(), env('PAYSTACK_SECRET_KEY'));

        if ($paystackSig !== $expectedSig) {
            Logged::create([
                'user_id'     => null,
                'from'        => 'KycController@webhook',
                'for'         => 'Webhook Signature Invalid',
                'message'     => 'Incoming webhook failed HMAC signature check.',
                'type'        => 'error',
                'stack_trace' => json_encode([
                    'full_payload' => $request->all(),
                    'received_sig' => $paystackSig,
                ]),
                't_reference' => null,
            ]);

            Log::warning('[KYC] webhook — Invalid HMAC signature', [
                'received_sig' => $paystackSig,
                'payload'      => $request->all(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event    = $request->input('event');
        $data     = $request->input('data', []);
        $customer = $data['customer'] ?? [];

        $user = User::where('email', $customer['email'] ?? '')->first();

        $webhookContext = [
            'full_payload' => $request->all(),
            'event'        => $event,
            'customer'     => $customer,
            'data'         => $data,
        ];

        Logged::create([
            'user_id'     => $user?->id,
            'from'        => 'KycController@webhook',
            'for'         => 'Paystack Webhook: ' . $event,
            'message'     => "Webhook [{$event}] for: " . ($customer['email'] ?? 'unknown'),
            'type'        => 'info',
            'stack_trace' => json_encode($webhookContext),
            't_reference' => $customer['customer_code'] ?? null,
        ]);

        Log::info("[KYC] webhook — Received [{$event}]", array_merge(
            ['user_id' => $user?->id, 'email' => $customer['email'] ?? 'unknown'],
            $webhookContext
        ));

        match ($event) {
            'customeridentification.success' => $this->handleSuccess($user, $data),
            'customeridentification.failed'  => $this->handleFailed($user, $data),
            default => Log::info("[KYC] webhook — Unhandled event: {$event}", ['payload' => $data]),
        };

        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * customeridentification.success
     * Paystack confirmed BVN matches the bank account — approve the KYC and update user name
     */
    private function handleSuccess(?User $user, array $data): void
    {
        if (!$user) {
            Log::warning('[KYC] handleSuccess — No matching user found for webhook payload', ['data' => $data]);
            return;
        }

        KycProfile::where('user_id', $user->id)->update(['status' => 'APPROVED']);

        /*
         * Update the user's name from the verified identity.
         * Paystack returns the verified name in data.customer.first_name / last_name.
         * Some responses also include it in data.identification — we fall back to that.
         */
        $firstName = $data['customer']['first_name']    ?? $data['identification']['first_name'] ?? null;
        $lastName  = $data['customer']['last_name']     ?? $data['identification']['last_name']  ?? null;

        $nameUpdated = false;

        if ($firstName || $lastName) {
            $fullName    = trim("{$firstName} {$lastName}");
            $user->name  = $fullName;
            $user->save();
            $nameUpdated = true;
        }

        // TODO: notify user via WhatsApp
        // app(WhatsAppController::class)->sendMessage($user->mobile, '...');

        $logMessage = "KYC APPROVED for {$user->name} ({$user->id}) — Paystack confirmed identity."
                    . ($nameUpdated ? " Name updated to: {$user->name}" : " Name not updated — no name in payload.");

        $logContext = [
            'full_payload' => $data,
            'name_update'  => [
                'updated'    => $nameUpdated,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'full_name'  => $nameUpdated ? $user->name : null,
            ],
        ];

        Logged::create([
            'user_id'     => $user->id,
            'from'        => 'KycController@handleSuccess',
            'for'         => 'KYC Approved via Paystack Webhook',
            'message'     => $logMessage,
            'type'        => 'info',
            'stack_trace' => json_encode($logContext),
            't_reference' => $data['customer']['customer_code'] ?? null,
        ]);

        Log::info("[KYC] handleSuccess — {$logMessage}", array_merge(
            ['user_id' => $user->id],
            $logContext
        ));
    }

    /**
     * customeridentification.failed
     * Paystack could not match the BVN — reject the KYC
     */
    private function handleFailed(?User $user, array $data): void
    {
        if (!$user) {
            Log::warning('[KYC] handleFailed — No matching user found for webhook payload', ['data' => $data]);
            return;
        }

        $reason = $data['reason'] ?? 'Identity could not be verified.';

        KycProfile::where('user_id', $user->id)->update([
            'status'           => 'REJECTED',
            'rejection_reason' => $reason,
        ]);

        $logContext = [
            'full_payload'     => $data,
            'rejection_reason' => $reason,
            'customer'         => $data['customer']      ?? [],
            'identification'   => $data['identification'] ?? [],
        ];

        Logged::create([
            'user_id'     => $user->id,
            'from'        => 'KycController@handleFailed',
            'for'         => 'KYC Rejected via Paystack Webhook',
            'message'     => "KYC REJECTED for {$user->name} ({$user->id}). Reason: {$reason}",
            'type'        => 'error',
            'stack_trace' => json_encode($logContext),
            't_reference' => $data['customer']['customer_code'] ?? null,
        ]);

        Log::error("[KYC] handleFailed — KYC REJECTED for {$user->name} ({$user->id})", array_merge(
            ['user_id' => $user->id, 'reason' => $reason],
            $logContext
        ));
    }

    /**
     * Final KYC form submission
     * Saves all fields + uploads documents
     * Status = PENDING until Paystack webhook updates it
     */
    public function submit(Request $request, $user)
    {
        $resolvedUserId = null;

        try {
            $resolvedUserId = decrypt($request->token);

            if ($resolvedUserId != $user) {
                return back()->withErrors(['error' => 'Invalid token']);
            }

            $user = User::findOrFail($user);
            $resolvedUserId = $user->id;

            if ($user->hasKyc()) {
                return redirect()->route('kyc.form', ['user' => $user->id, 'token' => $request->token])
                    ->with('error', 'KYC already submitted');
            }

            $validator = Validator::make($request->all(), [
                'bvn'              => 'required|digits:11',
                'nin'              => 'required|digits:11',
                'bvn_phone'        => 'required|min:10|max:11',
                'account_number'   => 'required|digits:10',
                'bank_code'        => 'required|string',
                'bvn_submitted'    => 'required|in:1',
                'passport_photo'   => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'proof_of_address' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                Log::warning('[KYC] submit — Validation failed', [
                    'user_id' => $resolvedUserId,
                    'errors'  => $validator->errors()->toArray(),
                ]);

                return back()->withErrors($validator)->withInput();
            }

            if (!session('kyc_bvn_submitted_' . $user->id)) {
                Logged::create([
                    'user_id'     => $resolvedUserId,
                    'from'        => 'KycController@submit',
                    'for'         => 'KYC Submit — BVN Not Submitted',
                    'message'     => 'Form submitted without first calling validateCustomer.',
                    'type'        => 'error',
                    'stack_trace' => null,
                    't_reference' => null,
                ]);

                Log::error('[KYC] submit — Form submitted without BVN validation step', [
                    'user_id' => $resolvedUserId,
                ]);

                return back()->withErrors(['bvn' => 'Please verify your BVN before submitting.'])->withInput();
            }

            // Upload passport photo
            $passportUrl = $this->uploadToCloudinary(
                $request->file('passport_photo'),
                'apay/kyc/passports',
                'passport_' . $user->id . '_' . time()
            );

            // Upload proof of address
            $proofUrl = $this->uploadToCloudinary(
                $request->file('proof_of_address'),
                'apay/kyc/proof',
                'proof_' . $user->id . '_' . time()
            );

            if (!$passportUrl || !$proofUrl) {
                Logged::create([
                    'user_id'     => $resolvedUserId,
                    'from'        => 'KycController@submit',
                    'for'         => 'KYC Document Upload Failed',
                    'message'     => 'Cloudinary upload failed for one or more KYC documents.',
                    'type'        => 'error',
                    'stack_trace' => json_encode(['passport' => $passportUrl, 'proof' => $proofUrl]),
                    't_reference' => null,
                ]);

                Log::error('[KYC] submit — Cloudinary upload failed', [
                    'user_id'  => $resolvedUserId,
                    'passport' => $passportUrl,
                    'proof'    => $proofUrl,
                ]);

                return back()->withErrors(['error' => 'Failed to upload documents. Please try again.'])->withInput();
            }

            // Save KYC — PENDING until Paystack webhook fires
            KycProfile::create([
                'user_id'          => $user->id,
                'bvn'              => $request->bvn,
                'nin'              => $request->nin,
                'bvn_phone_last_5' => substr($request->bvn_phone, -5),
                'passport_photo'   => $passportUrl,
                'proof_of_address' => $proofUrl,
                'status'           => 'PENDING',
                'rejection_reason' => null,
            ]);

            session()->forget('kyc_bvn_submitted_' . $user->id);

            Logged::create([
                'user_id'     => $user->id,
                'from'        => 'KycController@submit',
                'for'         => 'KYC Submission',
                'message'     => "KYC saved as PENDING for {$user->name} ({$user->id}). Awaiting Paystack webhook.",
                'type'        => 'info',
                'stack_trace' => null,
                't_reference' => null,
            ]);

            Log::info('[KYC] submit — KYC saved as PENDING', [
                'user_id' => $user->id,
                'name'    => $user->name,
            ]);

            $this->sendWhatsAppNotification($user);

            return view('kyc.success', compact('user'));

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => $resolvedUserId,
                'from'        => 'KycController@submit',
                'for'         => 'KYC Submission Exception',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => null,
            ]);

            Log::error('[KYC] submit — Exception thrown', [
                'user_id'   => $resolvedUserId,
                'error'     => $e->getMessage(),
                'exception' => $e,
            ]);

            return back()->withErrors(['error' => 'Something went wrong. Please try again.'])->withInput();
        }
    }

    /**
     * Upload a file to Cloudinary
     */
    private function uploadToCloudinary($file, string $folder, string $publicId): ?string
    {
        try {
            $upload = Cloudinary::upload($file->getRealPath(), [
                'folder'        => $folder,
                'public_id'     => $publicId,
                'resource_type' => 'auto',
                'overwrite'     => true,
                'invalidate'    => true,
                'quality'       => 'auto:best',
                'fetch_format'  => 'auto',
            ]);

            return $upload->getSecurePath();

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => null,
                'from'        => 'KycController@uploadToCloudinary',
                'for'         => 'Cloudinary Upload Failed',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => null,
            ]);

            Log::error('[KYC] uploadToCloudinary — Upload failed', [
                'public_id' => $publicId,
                'folder'    => $folder,
                'error'     => $e->getMessage(),
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Send WhatsApp notification after KYC is submitted
     */
    private function sendWhatsAppNotification(User $user): void
    {
        // TODO: implement WhatsApp
        // app(WhatsAppController::class)->sendMessage($user->mobile, $message);

        Logged::create([
            'user_id'     => $user->id,
            'from'        => 'KycController@sendWhatsAppNotification',
            'for'         => 'KYC WhatsApp Notification',
            'message'     => "WhatsApp notification queued for {$user->name} ({$user->id})",
            'type'        => 'info',
            'stack_trace' => null,
            't_reference' => null,
        ]);

        Log::info('[KYC] sendWhatsAppNotification — Notification queued', [
            'user_id' => $user->id,
            'name'    => $user->name,
        ]);
    }
}