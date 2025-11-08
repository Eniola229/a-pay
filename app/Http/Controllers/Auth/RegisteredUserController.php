<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationConfirmation;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;
use App\Models\EmailVerification;
use Carbon\Carbon;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function store(Request $request, SmsService $smsService)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'regex:/^\+234[0-9]{10}$/', 'unique:users'],
            'email_verification_code' => ['required'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'not_in:123456789,password,12345678,123123,qwerty',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
            ],
            'referer_mobile' => [
                'nullable',
                'regex:/^\+234[0-9]{10}$/',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // Check if referer exists in "users.mobile"
                        if (!\App\Models\User::where('mobile', $value)->exists()) {
                            $fail('The referer code (phone number) is invalid.');
                        }
                    }
                }
            ]
        ]);

        $verification = EmailVerification::where('email', $request->email)->first();
        $inputCode = (string) $request->email_verification_code;
        $dbCode = (string) $verification->code;

        if (!$verification || $dbCode !== $inputCode) {
            dd($inputCode);
            return back()->with('error', 'Invalid or expired email verification code.');
        }

        $verification->delete();

        $accountNumber = str_replace('+234', '', $request->mobile);

        $user = User::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_number' => $accountNumber,
        ]);

        // Check if referer code is in URL (or input)
        if ($request->has('referer_mobile') || $request->has('r_c')) {
            $refererCode = $request->input('referer_mobile') ?? $request->input('r_c');

            // Make sure it includes +234 format
            if (strpos($refererCode, '+234') !== 0) {
                $refererCode = '+234' . ltrim($refererCode, '0');
            }

            // Find referer by mobile
            $referer = User::where('mobile', $refererCode)->first();

            if ($referer) {
                // Update balance +100
                $balance = Balance::where('user_id', $referer->id)->first();
                if ($balance) {
                    $balance->increment('balance', 0);
                }

                // Create transaction record
                Transaction::create([
                    'user_id'     => $referer->id,
                    'amount'      => 0,
                    'cash_back'   => 0,
                    'charges'     => 0,
                    'beneficiary' => $referer->name . ' | ' . $referer->mobile,
                    'description' => "Referral bonus for inviting {$request->name}",
                    'status'      => 'success',
                ]);
            }
        }

        // Check if Customer Exists in Paystack
        $customerLookupResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/customer/{$user->email}");

        $customerLookupData = $customerLookupResponse->json();
        //Log::info('Paystack Customer Lookup Response:', $customerLookupData);
        $nameParts = explode(' ', trim($request->name), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : $firstName; // If no last name, duplicate first name
        
        if (isset($customerLookupData['data']['customer_code'])) {
            // Customer exists, update phone number
            $customerCode = $customerLookupData['data']['customer_code'];
            $updateResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->put("https://api.paystack.co/customer/{$customerCode}", [
                'email' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $request->mobile,
            ]);

            $updateData = $updateResponse->json();
            //Log::info('Paystack Customer Update Response:', $updateData);
        } else {
            // Customer does not exist, create a new one
            $customerResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/customer', [
                'email' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $request->mobile,
            ]);

            $customerData = $customerResponse->json();
            //Log::info('Paystack Customer Response:', $customerData);

            if (!isset($customerData['data']['customer_code'])) {
                return redirect()->back()->with('error', 'Failed to create Paystack customer.');
            }

            $customerCode = $customerData['data']['customer_code'];
        }

        // Request Virtual Account from Paystack
        $paystackResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/dedicated_account', [
            'customer' => $customerCode,
            'preferred_bank' => 'wema-bank',
            'country' => 'NG',
        ]);

        $responseData = $paystackResponse->json();
        //Log::info('Paystack Virtual Account Response:', $responseData);

        if (!isset($responseData['data']['account_number'])) {
            return redirect()->route('login')->with('success', 'Account created, Kindly Login!');
        }

        // Save Virtual Account
        $user->update([
            'account_number' => $responseData['data']['account_number'],
        ]);

        Balance::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        // Send SMS Notification
        $message = "Hello {$request->name}, thank you for registering with A-Pay!";
        
        try {
            $smsService->sendSms($request->mobile, $message);
            return redirect()->route('login')->with('success', 'Registration successful! Virtual account created, Kindly login.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Registration successful, but SMS could not be sent');
        }
    }


}