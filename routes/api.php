<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/set/webhook/{token}',
    [\App\Http\Controllers\TelegramController::class,'setWebhook'])->name('set.webhook');

