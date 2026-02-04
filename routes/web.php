<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PublicCalendar;
use App\Livewire\PolicyPage;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', PublicCalendar::class)->name('calendar');
Route::get('/policy', PolicyPage::class)->name('policy');
// Route::get('/app', fn() => redirect('/'));