<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Balance;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'not_in:123456789,password,12345678,123123,qwerty', 
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
            ]
        ]);

        $accountNumber = str_replace('+234', '', $request->mobile);

        $user = User::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_number' => $accountNumber,
        ]);

        // Check if Customer Exists in Paystack
        $customerLookupResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/customer/{$user->email}");

        $customerLookupData = $customerLookupResponse->json();
        Log::info('Paystack Customer Lookup Response:', $customerLookupData);
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
            Log::info('Paystack Customer Update Response:', $updateData);
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
            Log::info('Paystack Customer Response:', $customerData);

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
        Log::info('Paystack Virtual Account Response:', $responseData);

        if (!isset($responseData['data']['account_number'])) {
            return redirect()->back()->with('error', 'Failed to generate virtual account. Account created, Kindly Login!');
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
        $message = "Hello {$request->name}, thank you for registering with AfricPay! Your virtual account number is {$responseData['data']['account_number']}";
        
        try {
            $smsService->sendSms($request->mobile, $message);
            return redirect()->route('dashboard')->with('success', 'Registration successful! Virtual account created.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Registration successful, but SMS could not be sent: ' . $e->getMessage());
        }
    }


}