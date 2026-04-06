<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\ScheduleStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class VerifyScheduleKeyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Schedule $schedule
    ) {}

    /**
     * Get the middleware the job should be processed with.
     *
     * Prevents duplicate processing of the same schedule's verify job,
     * which could happen if the job is retried after a transient failure.
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping("verify_key_usage:{$this->schedule->id}"),
        ];
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

        $key = $this->schedule->room->key;

        // Was this key used for THIS schedule?
        // We check for a USED event that either:
        //   (a) Is directly attributed to this schedule, OR
        //   (b) Occurred within a 15-minute window before the schedule's start
        //       through its end (covers early grab scenarios)
        $wasUsed = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Used->value)
            ->where(function ($query) {
                $query->where('schedule_id', $this->schedule->id)
                    ->orWhereBetween('occurred_at', [
                        $this->schedule->start_time->copy()->subMinutes(15),
                        $this->schedule->end_time,
                    ]);
            })
            ->exists();

        if (! $wasUsed) {
            // Key was never taken out during this schedule — expire it
            $this->schedule->expire();

            return;
        }

        // Key was used — ensure post-class check is scheduled
        $runAt = $this->schedule->getPostClassCheckRunAt(10);
        if ($runAt->isFuture()) {
            PostClassCheckJob::dispatch($this->schedule)->delay($runAt);
        }
    }
}
