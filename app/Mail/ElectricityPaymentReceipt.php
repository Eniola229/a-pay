<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ElectricityPaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $details; // Declare public variable

    public function __construct($details)
    {
        $this->details = $details; // Store the details
    }

    public function build()
    {
        return $this->subject('Electricity Payment Receipt')
                    ->view('emails.electricity_receipt')
                    ->with([
                        'user'        => $this->details['user'],
                        'meterNumber' => $this->details['meterNumber'],
                        'provider'    => $this->details['provider'],
                        'amount'      => $this->details['amount'],
                        'token'      => $this->details['token'],
                        'units'      => $this->details['units'],
                        'status'      => $this->details['status'],
                    ]);
    }
}
