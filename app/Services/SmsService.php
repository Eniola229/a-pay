<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;

class SmsService
{
    protected $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );
    }

public function sendSms($to, $message)
{
    try {
        $this->twilio->messages->create(
            $to,
            [
                'messagingServiceSid' => config('services.twilio.messaging_service_sid'), // ← Changed
                'body' => $message,
            ]
        );
        return true;
    } catch (Exception $e) {
        throw new Exception("Failed to send SMS: " . $e->getMessage());
    }
}
}