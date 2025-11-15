<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeviceAuthentication;

class EnsureDeviceIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // If no user logged in, skip
        if (!$user) {
            return redirect()->route('login');
        }

        // Fetch latest device auth record
        $authRecord = DeviceAuthentication::where('user_id', $user->id)
            ->latest()
            ->first();

        // Check if no verification done or not verified yet
        if (!$authRecord || !$authRecord->verified) {
            // Logout the user and invalidate session
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'error' => 'Invalid device access. Please verify your device before proceeding.',
            ]);
        }

        // Optional: enforce that this device must match last verified device
        if ($user->last_login_device !== $authRecord->device_identifier) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized device. Please verify this device to continue.',
            ]);
        }

        return $next($request);
    }
}
