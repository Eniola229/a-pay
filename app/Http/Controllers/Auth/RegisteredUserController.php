<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
    // Validate the request
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
            ], [
                'password.required' => 'The password field is required.',
                'password.confirmed' => 'The passwords do not match.',
                'password.min' => 'The password must be at least 8 characters long.',
                'password.not_in' => 'The chosen password is too common. Please choose a stronger password.',
                'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ]);

    // Create the user
    $user = User::create([
        'name' => $request->name,
        'mobile' => $request->mobile,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // Send the registration confirmation email
    $verificationLink = route('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    Mail::to($user->email)->send(new RegistrationConfirmation($user->name, $verificationLink));

    // Send SMS
    $mobile = str_replace('+234', '', $request->mobile);
    $message = "Hello {$request->name}, thank you for registering with AfricPay! Your account number is {$mobile}";

    try {
        
         $smsService->sendSms($request->mobile, $message);
        return redirect()->route('dashboard')->with('success', 'Registration successful! SMS sent.');
    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Registration successful, but SMS could not be sent: ' . $e->getMessage());
    }
}
}