<?php

namespace App\Http\Controllers\Leaners;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leaners;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeLearnerMail;

class RegistrationController extends Controller
{
    public function showForm()
    {
        // Count current number of learners
        $count = Leaners::count();

        // Decide price
        $amount = 0;
        if ($count >= 10 && $count < 20) $amount = 20000;
        elseif ($count >= 20) $amount = 50000;

        return view('leaners', ['amount' => $amount]);
    }

    public function register(Request $request)
    {
        $count = Leaners::count();
        $amountRequired = 0;

        if ($count >= 10 && $count < 20) $amountRequired = 20000;
        elseif ($count >= 20) $amountRequired = 50000;

        // If payment is required, reference must be present and valid
        if ($amountRequired > 0) {
            if (!$request->filled('reference')) {
                return response()->json(['message' => 'Payment reference is required'], 400);
            }

            $reference = $request->reference;
            $verify = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if (!$verify->ok() || $verify['data']['status'] !== 'success' ||
                ($verify['data']['amount'] / 100) < $amountRequired) {
                return response()->json(['message' => 'Payment verification failed'], 400);
            }
        }

        // All checks passed, create learner
        $learner = Leaners::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'age' => $request->age,
            'sex' => $request->sex,
            'country' => $request->country,
            'state' => $request->state,
            'course_of_study' => $request->course_of_study,
            'is_student' => $request->has('is_student'),
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'amount_paid' => $amountRequired,
            'payment_status' => $amountRequired > 0 ? 'paid' : 'free',
        ]);

        Mail::to($learner->email)->send(new WelcomeLearnerMail($learner));

        return response()->json(['message' => 'Registration successful!']);
    }

}
