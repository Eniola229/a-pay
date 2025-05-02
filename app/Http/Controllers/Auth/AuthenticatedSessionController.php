<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\DeviceAuthentication;
use App\Mail\DeviceAuthenticationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(CustomLoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        $user = auth()->user();
        $deviceIdentifier = $request->ip() . '-' . $request->userAgent(); // Identify the device

        // Check if user has logged in before
        if (!$user->last_login_device || $user->last_login_device !== $deviceIdentifier) {
            // Generate 4-digit code
            $code = rand(1000, 9999);

            // Store in device authentication table
            $deviceAuth = DeviceAuthentication::updateOrCreate(
                ['user_id' => $user->id, 'device_identifier' => $deviceIdentifier],
                ['code' => $code, 'verified' => false]
            );

            // Send email with the 4-digit code
            Mail::to($user->email)->send(new DeviceAuthenticationMail($code));

            // Store device identifier in session (to verify after code input)
            session(['device_auth_id' => $deviceAuth->id]);

            return redirect()->route('verify.device');
        }

        // Update last login device
        $user->update(['last_login_device' => $deviceIdentifier]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
