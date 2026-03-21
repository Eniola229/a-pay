<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckFlowApprovalStatus extends Command
{
    protected $signature = 'apay:check-flow-status';
    protected $description = 'Check approval status of A-Pay Flow template';

    public function handle()
    {
        $sid = env('WHATSAPP_REGISTRATION_TEMPLATE_SID');

        $response = Http::withBasicAuth(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        )->get("https://content.twilio.com/v1/Content/{$sid}/ApprovalRequests");

        $data = $response->json();
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }
}