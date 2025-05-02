<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeLearnerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $learner;

    public function __construct($learner)
    {
        $this->learner = $learner;
    }

    public function build()
    {
        return $this->subject('🎉 Welcome to Leaners.Com!')
                    ->view('emails.welcome');
    }
}
