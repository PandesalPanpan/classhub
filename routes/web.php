<?php

use App\Http\Controllers\HandoverConfirmationController;
use App\Livewire\PolicyPage;
use App\Livewire\PublicCalendar;
use App\Livewire\TvCalendar;
use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', PublicCalendar::class)->name('calendar');
Route::get('/tv', TvCalendar::class)->name('tv-calendar');
Route::get('/policy', PolicyPage::class)->name('policy');

Route::middleware('signed')->group(function () {
    Route::get('/handover/{handover}/confirm', [HandoverConfirmationController::class, 'confirm'])
        ->name('handover.confirm');
    Route::get('/handover/{handover}/dispute', [HandoverConfirmationController::class, 'dispute'])
        ->name('handover.dispute');
});

Route::middleware(['auth', 'signed'])->prefix('admin/api')->group(function () {
    Route::get('/schedules/{schedule}/quick-approve', function (Schedule $schedule) {
        if (! Auth::user()?->can('Update:Schedule')) {
            abort(403);
        }

        if ($schedule->status !== ScheduleStatus::Pending) {
            Notification::make()
                ->title('Cannot approve')
                ->body('This schedule is no longer pending.')
                ->warning()
                ->send();

            return redirect()->route('filament.admin.pages.dashboard');
        }

        try {
            $schedule->approve();
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->implode(' ');

            Notification::make()
                ->title('Approval failed')
                ->body($messages)
                ->danger()
                ->send();

            return redirect()->route('filament.admin.pages.dashboard');
        }

        Notification::make()
            ->title('Schedule approved')
            ->body("{$schedule->subject} has been approved.")
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.dashboard');
    })->name('schedule.quick-approve');

    Route::get('/schedules/{schedule}/quick-reject', function (Schedule $schedule) {
        if (! Auth::user()?->can('Update:Schedule')) {
            abort(403);
        }

        if ($schedule->status !== ScheduleStatus::Pending) {
            Notification::make()
                ->title('Cannot reject')
                ->body('This schedule is no longer pending.')
                ->warning()
                ->send();

            return redirect()->route('filament.admin.pages.dashboard');
        }

        $schedule->reject();

        Notification::make()
            ->title('Schedule rejected')
            ->body("{$schedule->subject} has been rejected.")
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.dashboard');
    })->name('schedule.quick-reject');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/export/schedule-excel', [\App\Http\Controllers\ScheduleExportController::class, 'excel'])
        ->name('admin.schedule.export.excel');
    Route::get('/export/schedule-signsheet', [\App\Http\Controllers\ScheduleExportController::class, 'signSheet'])
        ->name('admin.schedule.export.signsheet');
});
