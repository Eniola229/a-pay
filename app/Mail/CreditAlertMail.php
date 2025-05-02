<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CreditAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $amount;
    public $transaction;

    public function __construct($user, $amount, $transaction)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->transaction = $transaction;
    }

    public function build()
    {
        return $this->subject('Credit Alert Notification')
                    ->view('emails.credit_alert');
    }
}
