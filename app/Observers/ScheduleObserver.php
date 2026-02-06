<?php

namespace App\Observers;

use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Support\Facades\Log;

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
        Log::info("Observer triggered for schedule {$schedule->id}");

        if (! $schedule->wasChanged('status') || $schedule->status !== ScheduleStatus::Approved) {
            Log::info("Schedule {$schedule->id} not approved, skipping verification");
            return;
        }

        if (! in_array($schedule->type, [ScheduleType::Request], true)) {
            Log::info("Schedule {$schedule->id} is not a request, skipping verification");
            return;
        }

        $schedule->load('room.key');
        if ($schedule->room?->key?->status === KeyStatus::Disabled) {
            Log::info("Schedule {$schedule->id} room key is disabled, skipping verification");
            return;
        }

        $runAt = $schedule->getFortyPercentDurationPoint();
        if (! $runAt->isFuture()) {
            Log::info("Schedule {$schedule->id} is not in the future, skipping verification");
            return;
        }

        VerifyScheduleKeyUsageJob::dispatch($schedule)->delay($runAt);
        Log::info("Schedule {$schedule->id} verification job dispatched");
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
