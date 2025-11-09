<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
//Ussd
use App\Http\Controllers\Ussd\UssdController;
use App\Http\Controllers\Ussd\DataController;
//Webhook
use App\Http\Controllers\Webhook\WhatsappController;

Route::get('/env-test', function () {
    return response()->json([
        'TWILIO_SID' => env('TWILIO_SID'),
        'TWILIO_AUTH_TOKEN' => env('TWILIO_AUTH_TOKEN'),
        'TWILIO_W_NUMBER' => env('TWILIO_W_NUMBER'),
    ]);
});


Route::post('/ussd', [UssdController::class, 'handle']);
Route::get('/data-plans/{networkId}', [DataController::class, 'getDataPlans']);
//webhook
Route::post('/whatsapp/webhook', [WhatsappController::class, 'handle']);
Route::get('/whatsapp/topup/callback', [WhatsappController::class, 'whatsappCallback'])->name('whatsapp.topup.callback');





