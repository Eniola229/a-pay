<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AirtimePurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $transaction;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $transaction, $status)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->status = $status;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Airtime Purchase ' . ucfirst($this->status))
                    ->view('emails.airtime_purchase');
    }
}
