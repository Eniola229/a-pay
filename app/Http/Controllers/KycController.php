<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KycProfile;
use App\Models\Logged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
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
     * Step 1: Initiate BVN verification via Paystack — sends OTP to BVN-linked phone
     */
    public function initiateBvn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn'     => 'required|digits:11',
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'BVN must be exactly 11 digits.',
            ], 400);
        }

        $userId = $request->user_id;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.paystack.co/identity/verify', [
                'type'  => 'bvn',
                'value' => $request->bvn,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? false) === true) {
                $reference = $data['data']['reference'] ?? null;

                if (!$reference) {
                    Logged::create([
                        'user_id'     => $userId,
                        'from'        => 'KycController@initiateBvn',
                        'for'         => 'Paystack BVN Initiation',
                        'message'     => 'Paystack returned success but no reference was found in the response.',
                        'type'        => 'error',
                        'stack_trace' => json_encode($data),
                        't_reference' => null,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Could not start verification. Please try again.',
                    ], 500);
                }

                // Cache reference + BVN + user_id for OTP step (expires in 10 min)
                Cache::put('kyc_bvn_ref_' . $request->bvn, [
                    'reference' => $reference,
                    'bvn'       => $request->bvn,
                    'user_id'   => $userId,
                ], now()->addMinutes(10));

                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@initiateBvn',
                    'for'         => 'Paystack BVN Initiation',
                    'message'     => 'OTP sent to BVN-linked phone. Verification session started.',
                    'type'        => 'info',
                    'stack_trace' => json_encode($data),
                    't_reference' => $reference,
                ]);

                return response()->json([
                    'success'      => true,
                    'message'      => 'OTP sent to your BVN-linked phone number.',
                    'reference'    => $reference,
                    'masked_phone' => $data['data']['phone_number'] ?? null,
                ]);
            }

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@initiateBvn',
                'for'         => 'Paystack BVN Initiation Failed',
                'message'     => $data['message'] ?? 'Paystack BVN initiation returned a failed status.',
                'type'        => 'error',
                'stack_trace' => json_encode([
                    'response'    => $data,
                    'status_code' => $response->status(),
                ]),
                't_reference' => null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'BVN verification could not be started. Please check your BVN.',
            ], 400);

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@initiateBvn',
                'for'         => 'BVN Initiation Exception',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service temporarily unavailable. Please try again.',
            ], 500);
        }
    }

    /**
     * Step 2: Confirm OTP sent to BVN-linked phone
     */
    public function confirmOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn'       => 'required|digits:11',
            'otp'       => 'required|digits:6',
            'reference' => 'required|string',
            'user_id'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP or reference.',
            ], 400);
        }

        $userId = $request->user_id;

        try {
            $cached = Cache::get('kyc_bvn_ref_' . $request->bvn);

            if (!$cached || $cached['reference'] !== $request->reference) {
                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@confirmOtp',
                    'for'         => 'OTP Session Validation',
                    'message'     => 'Verification session expired or reference mismatch.',
                    'type'        => 'error',
                    'stack_trace' => json_encode([
                        'cached'            => $cached,
                        'request_reference' => $request->reference,
                    ]),
                    't_reference' => $request->reference,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Verification session expired. Please restart.',
                ], 400);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.paystack.co/identity/verify/otp', [
                'reference' => $request->reference,
                'otp'       => $request->otp,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? false) === true) {
                $identity   = $data['data'] ?? [];
                $firstName  = $identity['first_name']  ?? '';
                $lastName   = $identity['last_name']   ?? '';
                $middleName = $identity['middle_name'] ?? '';
                $fullName   = trim("$firstName $middleName $lastName");

                // Store verified identity in cache for the final submit step (30 min)
                Cache::put('kyc_verified_' . $request->bvn, [
                    'bvn'       => $request->bvn,
                    'full_name' => $fullName,
                    'dob'       => $identity['dob'] ?? null,
                    'verified'  => true,
                    'user_id'   => $userId,
                ], now()->addMinutes(30));

                Cache::forget('kyc_bvn_ref_' . $request->bvn);

                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@confirmOtp',
                    'for'         => 'Paystack OTP Confirmed',
                    'message'     => "BVN identity verified successfully. Verified name: {$fullName}",
                    'type'        => 'info',
                    'stack_trace' => null,
                    't_reference' => $request->reference,
                ]);

                return response()->json([
                    'success'   => true,
                    'message'   => 'Identity verified successfully.',
                    'full_name' => $fullName,
                ]);
            }

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@confirmOtp',
                'for'         => 'Paystack OTP Confirmation Failed',
                'message'     => $data['message'] ?? 'Paystack OTP confirmation returned a failed status.',
                'type'        => 'error',
                'stack_trace' => json_encode([
                    'response'    => $data,
                    'status_code' => $response->status(),
                ]),
                't_reference' => $request->reference,
            ]);

            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'Invalid OTP. Please try again.',
            ], 400);

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@confirmOtp',
                'for'         => 'OTP Confirmation Exception',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => $request->reference ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not confirm OTP. Please try again.',
            ], 500);
        }
    }

    /**
     * Final KYC submission — all fields including documents
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
                'bvn'             => 'required|digits:11',
                'nin'             => 'required|digits:11',
                'bvn_phone'       => 'required|min:10|max:11',
                'bvn_verified'    => 'required|in:1',
                'passport_photo'  => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'proof_of_address'=> 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Confirm BVN was actually verified in this session
            $verified = Cache::get('kyc_verified_' . $request->bvn);

            if (!$verified || !($verified['verified'] ?? false)) {
                Logged::create([
                    'user_id'     => $resolvedUserId,
                    'from'        => 'KycController@submit',
                    'for'         => 'KYC Submission — BVN Not Verified',
                    'message'     => 'Submission attempted but no valid BVN verification session found in cache.',
                    'type'        => 'error',
                    'stack_trace' => null,
                    't_reference' => null,
                ]);

                return back()->withErrors([
                    'bvn' => 'Please complete BVN verification before submitting.',
                ])->withInput();
            }

            // Upload passport photo to Cloudinary
            $passportUrl = $this->uploadToCloudinary(
                $request->file('passport_photo'),
                'apay/kyc/passports',
                'passport_' . $user->id . '_' . time()
            );

            // Upload proof of address to Cloudinary
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
                    'message'     => 'One or more document uploads to Cloudinary failed.',
                    'type'        => 'error',
                    'stack_trace' => json_encode([
                        'passport_url' => $passportUrl,
                        'proof_url'    => $proofUrl,
                    ]),
                    't_reference' => null,
                ]);

                return back()->withErrors(['error' => 'Failed to upload documents. Please try again.'])->withInput();
            }

            // Save all KYC fields
            KycProfile::create([
                'user_id'          => $user->id,
                'bvn'              => $request->bvn,
                'nin'              => $request->nin,
                'bvn_phone_last_5' => substr($request->bvn_phone, -5),
                'passport_photo'   => $passportUrl,
                'proof_of_address' => $proofUrl,
                'status'           => 'APPROVED',
                'rejection_reason' => null,
            ]);

            Cache::forget('kyc_verified_' . $request->bvn);

            Logged::create([
                'user_id'     => $user->id,
                'from'        => 'KycController@submit',
                'for'         => 'KYC Submission',
                'message'     => "KYC submitted and auto-approved for user: {$user->name} ({$user->id})",
                'type'        => 'info',
                'stack_trace' => null,
                't_reference' => null,
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

            return null;
        }
    }

    /**
     * Queue WhatsApp notification to user after successful KYC
     */
    private function sendWhatsAppNotification(User $user): void
    {
        // TODO: Replace with your WhatsApp implementation
        // app(WhatsAppController::class)->sendMessage($user->mobile, $message);

        Logged::create([
            'user_id'     => $user->id,
            'from'        => 'KycController@sendWhatsAppNotification',
            'for'         => 'KYC WhatsApp Notification',
            'message'     => "WhatsApp KYC completion notification queued for user: {$user->name} ({$user->id})",
            'type'        => 'info',
            'stack_trace' => null,
            't_reference' => null,
        ]);
    }
}