<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KycProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    public function showForm(Request $request, $user)
    {
        try {
            $userId = decrypt($request->token);
            
            if ($userId != $user) {
                abort(403, 'Invalid token');
            }

            $user = User::findOrFail($user);

            // Check if already has KYC
            if ($user->hasKyc()) {
                return view('kyc.already-submitted', compact('user'));
            }

            return view('kyc.form', compact('user'));
        } catch (\Exception $e) {
            abort(403, 'Invalid or expired link');
        }
    }

    /**
     * Verify BVN using Nigerian BVN API
     */
    public function verifyBvn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bvn' => 'required|digits:11',
            'phone' => 'required|min:10|max:11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid BVN or phone number format'
            ], 400);
        }

        try {
            // OPTION 1: Paystack BVN Resolve (Correct Endpoint)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json'
            ])->get('https://api.paystack.co/bank/resolve_bvn/' . $request->bvn);

            $data = $response->json();

            Log::info('Paystack BVN Response:', $data);

            // Check if verification was successful
            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                // Get phone from BVN data
                $bvnData = $data['data'];
                $bvnPhone = $bvnData['mobile'] ?? $bvnData['phone_number'] ?? null;
                
                // Verify phone number matches (last 5 digits)
                if ($bvnPhone) {
                    // Remove country code if present
                    $bvnPhone = preg_replace('/^\+?234/', '0', $bvnPhone);
                    $requestPhone = preg_replace('/^\+?234/', '0', $request->phone);
                    
                    $requestPhoneLast5 = substr($requestPhone, -5);
                    $bvnPhoneLast5 = substr($bvnPhone, -5);

                    if ($requestPhoneLast5 !== $bvnPhoneLast5) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Phone number does not match BVN records'
                        ], 400);
                    }
                }

                $firstName = $bvnData['first_name'] ?? '';
                $lastName = $bvnData['last_name'] ?? '';
                $middleName = $bvnData['middle_name'] ?? '';

                return response()->json([
                    'success' => true,
                    'message' => 'BVN verified successfully',
                    'data' => [
                        'full_name' => trim($firstName . ' ' . $middleName . ' ' . $lastName),
                        'phone' => $bvnPhone,
                    ]
                ]);
            }

            // Log error for debugging
            Log::error('Paystack BVN verification failed', [
                'response' => $data,
                'status_code' => $response->status()
            ]);

            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'BVN verification failed. Please check your BVN.'
            ], 400);

        } catch (\Exception $e) {
            Log::error('BVN verification exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to verify BVN at this time. Please try again.'
            ], 500);
        }
    }

    public function submit(Request $request, $user)
    {
        try {
            $userId = decrypt($request->token);
            
            if ($userId != $user) {
                return back()->withErrors(['error' => 'Invalid token']);
            }

            $user = User::findOrFail($user);

            // Check if already has KYC
            if ($user->hasKyc()) {
                return redirect()->route('kyc.form', ['user' => $user->id, 'token' => $request->token])
                    ->with('error', 'KYC already submitted');
            }

            // Validate
            $validator = Validator::make($request->all(), [
                'bvn' => 'required|digits:11',
                'nin' => 'required|digits:11',
                'bvn_phone' => 'required|min:10|max:11',
                'bvn_verified' => 'required|in:1',
                'passport_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'proof_of_address' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Check if BVN was verified
            if ($request->bvn_verified !== '1') {
                return back()->withErrors([
                    'bvn' => 'Please verify your BVN before submitting'
                ])->withInput();
            }

            // Upload files to Cloudinary
            $passportUrl = $this->uploadToCloudinary(
                $request->file('passport_photo'),
                'apay/kyc/passports',
                'passport_' . $user->id . '_' . time()
            );

            $proofUrl = $this->uploadToCloudinary(
                $request->file('proof_of_address'),
                'apay/kyc/proof',
                'proof_' . $user->id . '_' . time()
            );

            if (!$passportUrl || !$proofUrl) {
                return back()->withErrors(['error' => 'Failed to upload documents. Please try again.'])->withInput();
            }

            // Create KYC Profile
            KycProfile::create([
                'user_id' => $user->id,
                'bvn' => $request->bvn,
                'nin' => $request->nin,
                'bvn_phone_last_5' => substr($request->bvn_phone, -5),
                'passport_photo' => $passportUrl,
                'proof_of_address' => $proofUrl,
                'status' => 'APPROVED', // Auto-approve but notify about manual review
            ]);

            // Send WhatsApp notification
            $this->sendWhatsAppNotification($user);

            return view('kyc.success', compact('user'));

        } catch (\Exception $e) {
            Log::error('KYC submission failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Something went wrong. Please try again.'])->withInput();
        }
    }

    /**
     * Upload file to Cloudinary
     */
    private function uploadToCloudinary($file, $folder, $publicId)
    {
        try {
            $upload = Cloudinary::upload($file->getRealPath(), [
                'folder' => $folder,
                'public_id' => $publicId,
                'resource_type' => 'auto', // Handles images and PDFs
                'overwrite' => true,
                'invalidate' => true,
                'quality' => 'auto:best', // Optimize quality
                'fetch_format' => 'auto', // Auto format
            ]);

            return $upload->getSecurePath();
        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed: ' . $e->getMessage(), [
                'folder' => $folder,
                'public_id' => $publicId
            ]);
            return null;
        }
    }

    /**
     * Send WhatsApp notification to user
     */
    private function sendWhatsAppNotification($user)
    {
        $message = "✅ *KYC VERIFICATION SUBMITTED* ✅\n\n".
                   "Thank you for completing your KYC verification!\n\n".
                   "Your account is now active and you can continue using all A-Pay services.\n\n".
                   "⚠️ *IMPORTANT:* Our team will review your documents within 24-48 hours. ".
                   "If we find any errors or discrepancies, your account will be temporarily suspended until corrections are made.\n\n".
                   "For any questions, contact support 📲 09079916807";

        // TODO: Implement your WhatsApp sending logic here
        // Example: 
        // app(WhatsAppController::class)->sendMessage($user->mobile, $message);
        
        Log::info('KYC WhatsApp notification queued for user: ' . $user->id);
    }
}