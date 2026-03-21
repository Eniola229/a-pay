<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegisterFlowTemplate extends Command
{
    protected $signature = 'apay:register-flow-template';
    protected $description = 'Register the A-Pay registration Flow template with Twilio';

    public function handle()
    {
        $response = Http::withBasicAuth(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        )->post('https://content.twilio.com/v1/Content', [
            'friendly_name' => 'apay_registration_flow',
            'language'      => 'en',
            'variables'     => [
                '1' => 'apay_token_123'
            ],
            'types' => [
                'whatsapp/flows' => [
                    'body'               => 'Welcome to A-Pay! 🚀 Tap below to create your free account.',
                    'button_text'        => 'Register Now',
                    'flow_id'            => env('WHATSAPP_REGISTRATION_FLOW_ID'),
                    'flow_token'         => '{{1}}',
                    'flow_first_page_id' => 'REGISTRATION',
                ]
            ]
        ]);

        $data = $response->json();

        if (isset($data['sid'])) {
            $this->info('✅ Template created! SID: ' . $data['sid']);
            $this->info('👉 Add this to your .env: WHATSAPP_REGISTRATION_TEMPLATE_SID=' . $data['sid']);
        } else {
            $this->error('❌ Failed');
            $this->line(json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}