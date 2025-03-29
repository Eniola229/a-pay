<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyUuidEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|string', // Ensure the ID is treated as a string
            'hash' => 'required|string',
        ];
    }

    public function fulfill()
    {
        $user = User::findOrFail($this->route('id'));

        if (!hash_equals($this->route('hash'), sha1($user->getEmailForVerification()))) {
            throw ValidationException::withMessages([
                'hash' => 'Invalid signature.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}