<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ClassroomCalendar;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', ClassroomCalendar::class)->name('calendar');
// Route::get('/app', fn() => redirect('/'));