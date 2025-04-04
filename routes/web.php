<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::controller(\App\Http\Controllers\PaymentController::class)->group(function () {
    Route::get('/payment/{user_id}/{amount}', 'goGateway');
    Route::get('/payment/verification', 'verification');
});
