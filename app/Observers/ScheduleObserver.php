<?php

namespace App\Observers;

use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;

class ScheduleObserver
{
    /**
     * Handle the Schedule "created" event.
     */
    public function created(Schedule $schedule): void
    {
        //
    }

    /**
     * Handle the Schedule "updated" event.
     */
    public function updated(Schedule $schedule): void
    {
        if (! $schedule->wasChanged('status') || $schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        if (! in_array($schedule->type, [ScheduleType::Request], true)) {
            return;
        }

        // Key jobs are now dispatched centrally via Schedule::dispatchKeyJobs()
        // which is called from Schedule::approve(). This observer just ensures
        // that schedules approved through non-model paths (e.g. bulk actions)
        // still get their jobs.
        $schedule->dispatchKeyJobs();
    }

    /**
     * Handle the Schedule "deleted" event.
     */
    public function deleted(Schedule $schedule): void
    {
        //
    }

    /**
     * Handle the Schedule "restored" event.
     */
    public function restored(Schedule $schedule): void
    {
        //
    }

    /**
     * Handle the Schedule "force deleted" event.
     */
    public function forceDeleted(Schedule $schedule): void
    {
        //
    }
}
