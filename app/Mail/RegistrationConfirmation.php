<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $verificationLink;

    /**
     * Create a new message instance.
     *
     * @param string $customerName
     * @param string $verificationLink
     */
    public function __construct($customerName, $verificationLink)
    {
        $this->customerName = $customerName;
        $this->verificationLink = $verificationLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to AfricPay')
                    ->view('emails.registration_confirmation')
                    ->with([
                        'customerName' => $this->customerName,
                        'verificationLink' => $this->verificationLink,
                    ]);
    }
}