<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::controller(\App\Http\Controllers\PaymentController::class)->group(function () {
    Route::get('/payment/{user_id}/{amount}', 'goGateway')->name('payment');
});
Route::get('/test-observer', function () {
    $game = \Apachish\Dabelna\App\Models\Game::first();
    $game->status = \Apachish\Dabelna\App\Models\Game::STATUS_WAITING_PLAYER;
    $game->save(); // باید updated() رو تریگر کنه
});
