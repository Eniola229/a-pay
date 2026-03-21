<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SubmitFlowTemplateApproval extends Command
{
    protected $signature = 'apay:submit-flow-approval';
    protected $description = 'Submit A-Pay Flow template for WhatsApp approval';

    public function handle()
    {
        $sid = env('WHATSAPP_REGISTRATION_TEMPLATE_SID');

        $response = Http::withBasicAuth(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        )->post("https://content.twilio.com/v1/Content/{$sid}/ApprovalRequests/whatsapp", [
            'name'     => 'apay_registration_flow',
            'category' => 'UTILITY',
        ]);

        $data = $response->json();
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }
}