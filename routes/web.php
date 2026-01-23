<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ClassroomCalendar;
use App\Livewire\PolicyPage;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', ClassroomCalendar::class)->name('calendar');
Route::get('/policy', PolicyPage::class)->name('policy');
// Route::get('/app', fn() => redirect('/'));