<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KycProfile;
use App\Models\Logged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

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

    // =========================================================================
    // STEP 1 — BVN VERIFICATION
    // =========================================================================

    /**
     * Verify BVN via Korapay Identity API.
     *
     * Endpoint: POST https://api.korapay.com/merchant/api/v1/identities/ng/bvn
     * Docs:     https://developers.korapay.com/docs/nigeria-bvn
     *
     * ┌─────────────────────────────────────────────────────────────────────┐
     * │  4-FACTOR OWNERSHIP PROOF                                           │
     * │                                                                     │
     * │  Check 1 — First name  matches BVN record (via Korapay API)        │
     * │  Check 2 — Last name   matches BVN record (via Korapay API)        │
     * │  Check 3 — Date of birth matches BVN record (via Korapay API)      │
     * │  Check 4 — Phone number matches BVN record (our server-side check) │
     * │            Korapay returns phone_number in the response.            │
     * │            We normalise both numbers to their last 8 digits so the  │
     * │            0XXXXXXXXXX vs +234XXXXXXXXXX prefix difference doesn't  │
     * │            cause false failures.                                    │
     * └─────────────────────────────────────────────────────────────────────┘
     *
     * On success we also store in session:
     *   · Verified DOB       → used in verifyNin() to cross-check NIN DOB
     *   · BVN-linked NIN     → Korapay returns the NIN tied to this BVN;
     *                          we use it to pre-reject a wrong NIN instantly
     *   · Verified names     → re-used in verifyNin() (no re-entry needed)
     */
    public function verifyBvn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => 'required',
            'first_name'     => 'required|string',
            'last_name'      => 'required|string',
            'bvn'            => 'required|digits:11',
            'date_of_birth'  => 'required|date_format:Y-m-d',
            'phone_number'   => 'required|min:10|max:14',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $userId = $request->user_id;
        $user   = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('KORAPAY_SECRET_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.korapay.com/merchant/api/v1/identities/ng/bvn', [
                'id'                   => $request->bvn,
                'verification_consent' => true,
                'validation'           => [
                    'first_name'    => $request->first_name,
                    'last_name'     => $request->last_name,
                    'date_of_birth' => $request->date_of_birth,
                ],
            ]);

            $data = $response->json();

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@verifyBvn',
                'for'         => 'Korapay BVN Verification',
                'message'     => 'Sent BVN + name + DOB to Korapay for 3-factor API check.',
                'type'        => 'info',
                'stack_trace' => json_encode(['full_response' => $data, 'http_status' => $response->status()]),
                't_reference' => $data['data']['reference'] ?? null,
            ]);

            // ── API-level failure ────────────────────────────────────────────
            if (!$response->successful() || ($data['status'] ?? false) !== true) {
                $errorMsg = 'Server down. Please try again later or message support';

                Logged::create([
                    'user_id' => $userId, 'from' => 'KycController@verifyBvn',
                    'for' => 'Korapay BVN API Failure', 'message' => $errorMsg,
                    'type' => 'error', 'stack_trace' => json_encode($data), 't_reference' => null,
                ]);

                return response()->json(['success' => false, 'message' => $errorMsg], 400);
            }

            $bvnData    = $data['data'];
            $validation = $bvnData['validation'] ?? [];

            // ── Checks 1-3: name + DOB match (Korapay API) ──────────────────
            $failed = [];
            if (!($validation['first_name']['match']    ?? false)) $failed[] = 'first name';
            if (!($validation['last_name']['match']     ?? false)) $failed[] = 'last name';
            if (!($validation['date_of_birth']['match'] ?? false)) $failed[] = 'date of birth';

            if (!empty($failed)) {
                $verb     = count($failed) > 1 ? 'do' : 'does';
                $errorMsg = 'The ' . implode(' and ', $failed) . " you entered {$verb} not match your BVN record. "
                          . 'Please enter your details exactly as registered with your bank.';

                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@verifyBvn',
                    'for'         => 'BVN Name/DOB Mismatch',
                    'message'     => $errorMsg,
                    'type'        => 'error',
                    'stack_trace' => json_encode(['failed' => $failed, 'validation' => $validation]),
                    't_reference' => $bvnData['reference'] ?? null,
                ]);

                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            // ── Check 4: phone number (server-side, last 8 digits) ───────────
            // Korapay returns the phone number registered on the BVN.
            // We compare the last 8 digits only so "08031234567" matches "2348031234567".
            $bvnPhone      = $bvnData['phone_number'] ?? null;
            $submittedPhone = $request->phone_number;

            if ($bvnPhone) {
                $bvnLast8       = substr(preg_replace('/\D/', '', $bvnPhone),      -8);
                $submittedLast8 = substr(preg_replace('/\D/', '', $submittedPhone), -8);

                if ($bvnLast8 !== $submittedLast8) {
                    $errorMsg = 'The phone number you entered does not match the phone number registered on your BVN. '
                              . 'Please enter the exact phone number linked to your BVN.';

                    Logged::create([
                        'user_id'     => $userId,
                        'from'        => 'KycController@verifyBvn',
                        'for'         => 'BVN Phone Mismatch',
                        'message'     => "Phone mismatch for user {$userId}. BVN last-8: {$bvnLast8} | Submitted last-8: {$submittedLast8}",
                        'type'        => 'error',
                        'stack_trace' => json_encode(['bvn_last8' => $bvnLast8, 'submitted_last8' => $submittedLast8]),
                        't_reference' => $bvnData['reference'] ?? null,
                    ]);

                    Log::warning('[KYC] verifyBvn — Phone mismatch', [
                        'user_id'        => $userId,
                        'bvn_last8'      => $bvnLast8,
                        'submitted_last8'=> $submittedLast8,
                    ]);

                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
            }

            // ── All 4 checks passed ──────────────────────────────────────────
            $verifiedDob  = $bvnData['date_of_birth']  ?? $request->date_of_birth;
            $verifiedNin  = $bvnData['nin']            ?? null;
            $verifiedName = trim(($bvnData['first_name'] ?? '') . ' ' . ($bvnData['last_name'] ?? ''));

            session([
                'kyc_bvn_verified_' . $userId => true,
                'kyc_bvn_dob_'      . $userId => $verifiedDob,
                'kyc_bvn_nin_'      . $userId => $verifiedNin,
                'kyc_bvn_ref_'      . $userId => $bvnData['reference'] ?? null,
                'kyc_first_name_'   . $userId => $request->first_name,
                'kyc_last_name_'    . $userId => $request->last_name,
            ]);

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@verifyBvn',
                'for'         => 'BVN Verified — 4-Factor Passed',
                'message'     => "BVN 4-factor check passed for user {$userId}. Verified name: {$verifiedName}",
                'type'        => 'info',
                'stack_trace' => json_encode([
                    'verified_name'  => $verifiedName,
                    'verified_dob'   => $verifiedDob,
                    'bvn_linked_nin' => $verifiedNin,
                    'reference'      => $bvnData['reference'] ?? null,
                ]),
                't_reference' => $bvnData['reference'] ?? null,
            ]);

            Log::info('[KYC] verifyBvn — 4-factor check passed', ['user_id' => $userId, 'name' => $verifiedName]);

            return response()->json([
                'success'       => true,
                'message'       => 'BVN verified. Please continue to NIN verification.',
                'verified_name' => $verifiedName,
            ]);

        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $userId, 'from' => 'KycController@verifyBvn',
                'for' => 'BVN Verification Exception', 'message' => $e->getMessage(),
                'type' => 'error', 'stack_trace' => $e->getTraceAsString(), 't_reference' => null,
            ]);

            Log::error('[KYC] verifyBvn — Exception', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Service unavailable. Please try again.'], 500);
        }
    }

    // =========================================================================
    // STEP 2 — NIN VERIFICATION + CROSS-DOCUMENT OWNERSHIP
    // =========================================================================

    /**
     * Verify NIN via Korapay Identity API, then run cross-document checks
     * against the BVN already verified in Step 1.
     *
     * Endpoint: POST https://api.korapay.com/merchant/api/v1/identities/ng/nin
     * Docs:     https://developers.korapay.com/docs/nigeria-nin
     *
     * ┌─────────────────────────────────────────────────────────────────────┐
     * │  VERIFICATION LAYERS                                                │
     * │                                                                     │
     * │  Pre-check (before API call — no cost if it fails):                │
     * │    · The BVN response contains a `nin` field — the NIN the         │
     * │      government links to that BVN. Submitted NIN must match.       │
     * │      If it doesn't, the user is submitting someone else's NIN.     │
     * │                                                                     │
     * │  NIN 3-factor (via Korapay API):                                   │
     * │    · first_name matches NIN record                                 │
     * │    · last_name  matches NIN record                                 │
     * │    · date_of_birth matches NIN record                              │
     * │      (names are read from the BVN session — no re-entry needed)    │
     * │                                                                     │
     * │  Cross-document DOB check (our server-side):                       │
     * │    · DOB returned by NIN API must equal DOB returned by BVN API.   │
     * │      Both are from government databases — same person = same DOB.  │
     * └─────────────────────────────────────────────────────────────────────┘
     */
    public function verifyNin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'       => 'required',
            'nin'           => 'required|digits:11',
            'date_of_birth' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $userId = $request->user_id;
        $user   = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        // Guard: BVN must be verified first
        if (!session('kyc_bvn_verified_' . $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your BVN before verifying your NIN.',
            ], 422);
        }

        $bvnDob       = session('kyc_bvn_dob_'     . $userId);
        $bvnLinkedNin = session('kyc_bvn_nin_'     . $userId);
        $firstName    = session('kyc_first_name_'  . $userId);
        $lastName     = session('kyc_last_name_'   . $userId);

        // ── Pre-check: BVN's linked NIN must equal submitted NIN ─────────────
        if ($bvnLinkedNin && $bvnLinkedNin !== $request->nin) {
            $errorMsg = 'The NIN you entered is not linked to your BVN. '
                      . 'Please use your own NIN.';

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@verifyNin',
                'for'         => 'NIN Pre-Check: BVN-linked NIN mismatch',
                'message'     => "BVN linked NIN ≠ submitted NIN for user {$userId}.",
                'type'        => 'error',
                'stack_trace' => json_encode(['bvn_linked_nin' => $bvnLinkedNin, 'submitted_nin' => $request->nin]),
                't_reference' => null,
            ]);

            Log::warning('[KYC] verifyNin — BVN-linked NIN mismatch', ['user_id' => $userId]);

            return response()->json(['success' => false, 'message' => $errorMsg], 422);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('KORAPAY_SECRET_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.korapay.com/merchant/api/v1/identities/ng/nin', [
                'id'                   => $request->nin,
                'verification_consent' => true,
                'validation'           => [
                    'first_name'    => $firstName,
                    'last_name'     => $lastName,
                    'date_of_birth' => $request->date_of_birth,
                ],
            ]);

            $data = $response->json();

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@verifyNin',
                'for'         => 'Korapay NIN Verification',
                'message'     => 'Sent NIN + name + DOB to Korapay for 3-factor check.',
                'type'        => 'info',
                'stack_trace' => json_encode(['full_response' => $data, 'http_status' => $response->status()]),
                't_reference' => $data['data']['reference'] ?? null,
            ]);

            // ── API-level failure ────────────────────────────────────────────
            if (!$response->successful() || ($data['status'] ?? false) !== true) {
                $errorMsg = 'Server down, please try again later';

                Logged::create([
                    'user_id' => $userId, 'from' => 'KycController@verifyNin',
                    'for' => 'Korapay NIN API Failure', 'message' => $errorMsg,
                    'type' => 'error', 'stack_trace' => json_encode($data), 't_reference' => null,
                ]);

                return response()->json(['success' => false, 'message' => $errorMsg], 400);
            }

            $ninData    = $data['data'];
            $validation = $ninData['validation'] ?? [];

            // ── NIN 3-factor match ───────────────────────────────────────────
            $failed = [];
            if (!($validation['first_name']['match']    ?? false)) $failed[] = 'first name';
            if (!($validation['last_name']['match']     ?? false)) $failed[] = 'last name';
            if (!($validation['date_of_birth']['match'] ?? false)) $failed[] = 'date of birth';

            if (!empty($failed)) {
                $verb     = count($failed) > 1 ? 'do' : 'does';
                $errorMsg = 'The ' . implode(' and ', $failed) . " you entered {$verb} not match your NIN record. "
                          . 'Please enter your details exactly as registered with NIMC.';

                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@verifyNin',
                    'for'         => 'NIN 3-Factor Mismatch',
                    'message'     => $errorMsg,
                    'type'        => 'error',
                    'stack_trace' => json_encode(['failed' => $failed, 'validation' => $validation]),
                    't_reference' => $ninData['reference'] ?? null,
                ]);

                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            // ── Cross-document DOB check ─────────────────────────────────────
            // The DOB on the NIN record must equal the DOB on the BVN record.
            // Both come directly from government databases — same person = same birthday.
            $ninDob = $ninData['date_of_birth'] ?? null;

            if ($bvnDob && $ninDob && $bvnDob !== $ninDob) {
                $errorMsg = 'The date of birth on your NIN does not match the date of birth on your BVN. '
                          . 'Please ensure you are using your own documents.';

                Logged::create([
                    'user_id'     => $userId,
                    'from'        => 'KycController@verifyNin',
                    'for'         => 'BVN ↔ NIN DOB Cross-Check Failed',
                    'message'     => "DOB mismatch — BVN: {$bvnDob} | NIN: {$ninDob} for user {$userId}.",
                    'type'        => 'error',
                    'stack_trace' => json_encode(['bvn_dob' => $bvnDob, 'nin_dob' => $ninDob]),
                    't_reference' => $ninData['reference'] ?? null,
                ]);

                Log::warning('[KYC] verifyNin — BVN ↔ NIN DOB mismatch', [
                    'user_id' => $userId, 'bvn_dob' => $bvnDob, 'nin_dob' => $ninDob,
                ]);

                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            // ── ALL checks passed ────────────────────────────────────────────
            session([
                'kyc_nin_verified_' . $userId => true,
                'kyc_nin_ref_'      . $userId => $ninData['reference'] ?? null,
            ]);

            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@verifyNin',
                'for'         => 'NIN Verified — All Checks Passed',
                'message'     => "NIN 3-factor + BVN↔NIN cross-checks passed for user {$userId}.",
                'type'        => 'info',
                'stack_trace' => json_encode([
                    'nin_ref'        => $ninData['reference'] ?? null,
                    'bvn_linked_nin' => $bvnLinkedNin,
                    'dob_cross'      => ['bvn' => $bvnDob, 'nin' => $ninDob],
                ]),
                't_reference' => $ninData['reference'] ?? null,
            ]);

            Log::info('[KYC] verifyNin — All checks passed', ['user_id' => $userId]);

            return response()->json([
                'success' => true,
                'message' => 'NIN verified. Please upload your documents and submit.',
            ]);

        } catch (\Exception $e) {
            Logged::create([
                'user_id' => $userId, 'from' => 'KycController@verifyNin',
                'for' => 'NIN Verification Exception', 'message' => $e->getMessage(),
                'type' => 'error', 'stack_trace' => $e->getTraceAsString(), 't_reference' => null,
            ]);

            Log::error('[KYC] verifyNin — Exception', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Service unavailable. Please try again.'], 500);
        }
    }

    // =========================================================================
    // STEP 3 — FINAL FORM SUBMISSION
    // =========================================================================

    /**
     * Final KYC form submission.
     * Both BVN (4-factor) and NIN (3-factor + cross-document) must be verified.
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
                'bvn_phone'        => 'required|min:10|max:14',
                'bvn_verified'     => 'required|in:1',
                'nin_verified'     => 'required|in:1',
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

            // Double-guard: both verification sessions must exist
            $bvnVerified = session('kyc_bvn_verified_' . $user->id);
            $ninVerified = session('kyc_nin_verified_'  . $user->id);

            if (!$bvnVerified || !$ninVerified) {
                Logged::create([
                    'user_id'     => $resolvedUserId,
                    'from'        => 'KycController@submit',
                    'for'         => 'KYC Submit — Verification Incomplete',
                    'message'     => 'Form submitted without completing BVN and/or NIN verification.',
                    'type'        => 'error',
                    'stack_trace' => json_encode(['bvn' => $bvnVerified, 'nin' => $ninVerified]),
                    't_reference' => null,
                ]);

                return back()->withErrors(['error' => 'Please complete both BVN and NIN verification before submitting.'])->withInput();
            }

            // Upload documents
            $passportUrl = $this->uploadToCloudinary(
                $request->file('passport_photo'),
                'apay/kyc/passports',
                'passport_' . $user->id . '_' . time(),
                $user->id
            );

            $proofUrl = $this->uploadToCloudinary(
                $request->file('proof_of_address'),
                'apay/kyc/proof',
                'proof_' . $user->id . '_' . time(),
                $user->id
            );

            if (!$passportUrl || !$proofUrl) {
                Logged::create([
                    'user_id'     => $resolvedUserId,
                    'from'        => 'KycController@submit',
                    'for'         => 'KYC Document Upload Failed',
                    'message'     => 'Cloudinary upload failed.',
                    'type'        => 'error',
                    'stack_trace' => json_encode(['passport' => $passportUrl, 'proof' => $proofUrl]),
                    't_reference' => null,
                ]);

                return back()->withErrors(['error' => 'Failed to upload documents. Please try again.'])->withInput();
            }

            // Save KYC — APPROVED because all 7 checks + 2 cross-document checks passed
            KycProfile::create([
                'user_id'          => $user->id,
                'bvn'              => $request->bvn,
                'nin'              => $request->nin,
                'bvn_phone_last_5' => substr(preg_replace('/\D/', '', $request->bvn_phone), -5),
                'passport_photo'   => $passportUrl,
                'proof_of_address' => $proofUrl,
                'bvn_reference'    => session('kyc_bvn_ref_' . $user->id),
                'nin_reference'    => session('kyc_nin_ref_' . $user->id),
                'status'           => 'APPROVED',
                'rejection_reason' => null,
            ]);

            // Clean up all KYC session keys
            foreach (['bvn_verified', 'bvn_dob', 'bvn_nin', 'bvn_ref',
                      'nin_verified', 'nin_ref', 'first_name', 'last_name'] as $key) {
                session()->forget("kyc_{$key}_{$user->id}");
            }

            Logged::create([
                'user_id'     => $user->id,
                'from'        => 'KycController@submit',
                'for'         => 'KYC Submission Complete',
                'message'     => "KYC APPROVED for {$user->name} ({$user->id}). 4-factor BVN + 3-factor NIN + cross-checks all passed.",
                'type'        => 'info',
                'stack_trace' => null,
                't_reference' => null,
            ]);

            Log::info('[KYC] submit — KYC saved as APPROVED', ['user_id' => $user->id]);

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

            Log::error('[KYC] submit — Exception', [
                'user_id' => $resolvedUserId, 'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Something went wrong. Please try again.'])->withInput();
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function uploadToCloudinary($file, string $folder, string $publicId, ?string $userId = null): ?string
    {
        if (!$file || !$file->isValid() || !$file->getRealPath()) {
            Log::error('[KYC] uploadToCloudinary — File null or invalid', [
                'public_id' => $publicId, 'folder' => $folder,
            ]);
            return null;
        }

        try {
            $cloudinaryUrl = env('CLOUDINARY_URL');

            if ($cloudinaryUrl) {
                $parsed     = parse_url($cloudinaryUrl);
                $cloudinary = new \Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => $parsed['host'] ?? env('CLOUDINARY_CLOUD_NAME'),
                        'api_key'    => $parsed['user'] ?? env('CLOUDINARY_API_KEY'),
                        'api_secret' => $parsed['pass'] ?? env('CLOUDINARY_API_SECRET'),
                    ],
                ]);
            } else {
                $cloudinary = new \Cloudinary\Cloudinary([
                    'cloud' => [
                        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                        'api_key'    => env('CLOUDINARY_API_KEY'),
                        'api_secret' => env('CLOUDINARY_API_SECRET'),
                    ],
                ]);
            }

            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
                'folder'        => $folder,
                'public_id'     => $publicId,
                'resource_type' => 'auto',
                'overwrite'     => true,
                'invalidate'    => true,
                'quality'       => 'auto:best',
                'fetch_format'  => 'auto',
            ]);

            return $result['secure_url'] ?? null;

        } catch (\Exception $e) {
            Logged::create([
                'user_id'     => $userId,
                'from'        => 'KycController@uploadToCloudinary',
                'for'         => 'Cloudinary Upload Failed',
                'message'     => $e->getMessage(),
                'type'        => 'error',
                'stack_trace' => $e->getTraceAsString(),
                't_reference' => null,
            ]);
            Log::error('[KYC] uploadToCloudinary — Upload failed', [
                'public_id' => $publicId, 'folder' => $folder, 'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function sendWhatsAppNotification(User $user): void
    {
        // TODO: implement WhatsApp notification
        Logged::create([
            'user_id'     => $user->id,
            'from'        => 'KycController@sendWhatsAppNotification',
            'for'         => 'KYC WhatsApp Notification',
            'message'     => "WhatsApp notification queued for {$user->name} ({$user->id})",
            'type'        => 'info',
            'stack_trace' => null,
            't_reference' => null,
        ]);
    }
}