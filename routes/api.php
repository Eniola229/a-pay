<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ussd\UssdController;
use App\Http\Controllers\Ussd\DataController;

Route::post('/ussd', [UssdController::class, 'handle']);
Route::get('/data-plans/{networkId}', [DataController::class, 'getDataPlans']);


