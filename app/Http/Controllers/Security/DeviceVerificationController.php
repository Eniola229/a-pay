<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\DeviceAuthentication;
use App\Models\User;

class DeviceVerificationController extends Controller
{
    public function show()
    {
        return view('auth.two-factor-auth');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:4',
        ]);

        $user = auth()->user();
        $authCode = DeviceAuthentication::where('user_id', $user->id)->latest()->first();

        if ($authCode && $authCode->code == $request->code) {
            // Mark as verified
            $authCode->update(['verified' => true]);

            // Update the last login device for the user
            $user->update(['last_login_device' => $authCode->device_identifier]);

            return redirect()->route('dashboard')->with('success', 'Device verified successfully.');
        }

        // Log the user out if the code is incorrect
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['error' => 'Invalid code. Please kindly login again.']);
    }

}
