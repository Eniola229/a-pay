<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactInquiryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'complaint' => 'required|string',
            'category' => 'required|string|max:100',
        ]);

        $inquiry = ContactInquiry::create($request->all());

        // Send email notification
        Mail::send('emails.contact_inquiry', ['inquiry' => $inquiry], function ($message) use ($inquiry) {
            $message->to('africteam@gmail.com')
                    ->subject('New Contact Inquiry from ' . $inquiry->name);
        });

        return response()->json(['message' => 'Thank you for reaching out to us. We will respond to you soon via email and the phone number you provided.']);
    }
}
