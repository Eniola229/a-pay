<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BettingPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $customerID;
    public $serviceID;
    public $amountPaid;
    public $status;

    public function __construct($user, $customerID, $serviceID, $amountPaid, $status)
    {
        $this->user = $user;
        $this->customerID = $customerID;
        $this->serviceID = $serviceID;
        $this->amountPaid = $amountPaid;
        $this->status = $status;
    }

    public function build()
    {
        return $this->subject('Betting Purchase ' . ($this->status == 'SUCCESS' ? 'Successful' : 'Failed'))
                    ->view('emails.betting_purchase');
    }
}
