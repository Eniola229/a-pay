<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class CustomLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^\+234[0-9]{10}$/'], // Ensure +234 prefix
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Find user
        $user = User::where('mobile', $this->mobile)->first();

        //BLOCKED ACCOUNT CHECK
        if ($user && $user->is_status === 'BLOCKED') {
            throw ValidationException::withMessages([
                'mobile' => '🚫 Your account has been BLOCKED. Please contact Customer Support at 09079916807.',
            ]);
        }

        // Empty password check (security)
        if ($user && empty($user->password)) {
            throw ValidationException::withMessages([
                'mobile' => '⚠️ For security reasons, please reset your password before logging in.',
            ]);
        }

        // Attempt login
        if (!auth()->attempt([
            'mobile' => $this->mobile,
            'password' => $this->password
        ])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'mobile' => '❌ Incorrect Phone Number or Password.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'mobile' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->input('mobile')) . '|' . $this->ip()
        );
    }
}
