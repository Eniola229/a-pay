<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class VerifyEmailController extends Controller
{
    /**
     * Handle the email verification request.
     */
    public function __invoke(Request $request, $id, $hash)
    {
        // Manually validate the signed URL
        if (!URL::hasValidSignature($request)) {
            throw new InvalidSignatureException;
        }

        // Find the user by UUID
        $user = User::where('id', $id)->firstOrFail();

        // Debug the user, ID, and hash
        dd([
            'user' => $user,
            'id' => $id,
            'hash' => $hash,
            'expected_hash' => sha1($user->getEmailForVerification()),
        ]);

        // Verify the hash
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid signature.');
        }

        // Mark the email as verified
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect('/dashboard')->with('verified', true);
    }
}