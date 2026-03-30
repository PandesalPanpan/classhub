<?php

namespace App\Observers;

use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
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

        $schedule->load('room.key');
        if ($schedule->room?->key?->status === KeyStatus::Disabled) {
            return;
        }

        $runAt = $schedule->getFortyPercentDurationPoint();
        if (! $runAt->isFuture()) {
            return;
        }

        VerifyScheduleKeyUsageJob::dispatch($schedule)->delay($runAt);
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
