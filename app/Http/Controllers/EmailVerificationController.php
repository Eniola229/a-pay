<?php

// app/Http/Controllers/EmailVerificationController.php
namespace App\Http\Controllers;

use App\Models\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $code = rand(100000, 999999);

        EmailVerification::updateOrCreate(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]
        );

        Mail::send('emails.verify-code', ['code' => $code], function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Your Email Verification Code');
        });

        return response()->json(['success' => true, 'message' => 'Verification code sent!']);
    }
}
