<?php

use App\Http\Controllers\HandoverConfirmationController;
use App\Livewire\PolicyPage;
use App\Livewire\PublicCalendar;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicCalendar::class)->name('calendar');
Route::get('/policy', PolicyPage::class)->name('policy');

Route::middleware('signed')->group(function () {
    Route::get('/handover/{handover}/confirm', [HandoverConfirmationController::class, 'confirm'])
        ->name('handover.confirm');
    Route::get('/handover/{handover}/dispute', [HandoverConfirmationController::class, 'dispute'])
        ->name('handover.dispute');
});
