<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DataPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $phoneNumber;
    public $planName;
    public $amount;
    public $status;

    public function __construct($user, $phoneNumber, $planName, $amount, $status)
    {
        $this->user = $user;
        $this->phoneNumber = $phoneNumber;
        $this->planName = $planName;
        $this->amount = $amount;
        $this->status = $status;
    }

    public function build()
    {
        return $this->subject('Data Purchase ' . ($this->status == 'SUCCESS' ? 'Successful' : 'Failed'))
                    ->view('emails.data_purchase');
    }
}
