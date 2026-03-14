<?php

use App\Http\Controllers\BroadcastingConfigController;
use App\Http\Controllers\KeyController;
use Illuminate\Support\Facades\Route;

Route::get('/broadcasting/config', BroadcastingConfigController::class)->name('broadcasting.config');

Route::middleware('verifyIotRoomKey')->group(function () {
    Route::get('/keys', [KeyController::class, 'index']);
    Route::get('/keys/by-slot/{slot_number}', [KeyController::class, 'showBySlot']);
    Route::put('/keys/by-slot/{slot_number}/status', [KeyController::class, 'updateStatus']);
});
