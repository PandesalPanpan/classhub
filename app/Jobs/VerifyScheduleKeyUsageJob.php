<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\Schedule;
use App\ScheduleStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyScheduleKeyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Schedule $schedule
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh the schedule to get latest data and relationships
        $this->schedule->refresh();
        $this->schedule->load('room.key');

        // Only process if schedule is still approved
        // It might have been cancelled or expired before this job ran
        if ($this->schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        // Check if room and key exist
        if (! $this->schedule->room || ! $this->schedule->room->key) {
            return;
        }

        $keyStatus = $this->schedule->room->key->status;

        // If key is "Stored", expire the schedule
        // If key is "Used" or "Disabled", ignore it
        if ($keyStatus === KeyStatus::Stored) {
            $this->schedule->expire();
        }
    }
}
