<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ClassroomCalendar;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/calendar', ClassroomCalendar::class)->name('calendar');
