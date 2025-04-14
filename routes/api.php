<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/set/webhook/{token}',
    [\App\Http\Controllers\TelegramController::class,'setWebhook'])->name('set.webhook');
Route::post('accounting/set/webhook/{token}',
    [\App\Http\Controllers\TelegramAccountingController::class,'setWebhook'])->name('set.webhook.accounting');

Route::controller(\App\Http\Controllers\PaymentController::class)->group(function () {
    Route::post('/payment/verification/{authority}', 'verification');
});
