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
        if ($schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        if ($schedule->type === ScheduleType::Template) {
            return;
        }

        $schedule->dispatchKeyJobs();
        $schedule->reconcileNeighborHandovers();
        $schedule->cancelOverlappingTemplates();
    }

    /**
     * Handle the Schedule "updated" event.
     */
    public function updated(Schedule $schedule): void
    {
        if (! $schedule->wasChanged('status') || $schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        if (in_array($schedule->type, [ScheduleType::Request], true)) {
            $schedule->dispatchKeyJobs();
        }

        $schedule->reconcileNeighborHandovers();
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
