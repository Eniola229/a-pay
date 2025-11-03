<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ussd\UssdController;

Route::post('/ussd', [UssdController::class, 'handle']);


