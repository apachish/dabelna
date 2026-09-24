<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});


Route::controller(\App\Http\Controllers\PaymentController::class)->group(function () {
    Route::get('/payment/{user_id}/{amount}', 'goGateway')->name('payment');
});
