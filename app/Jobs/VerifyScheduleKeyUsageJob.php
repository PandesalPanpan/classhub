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
        $this->schedule->refresh();
        $this->schedule->load('room.key');

        if ($this->schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        if (! $this->schedule->room || ! $this->schedule->room->key) {
            return;
        }

        $key = $this->schedule->room->key;

        // Check for ANY USED event — IoT scan OR synthetic handover.
        // A handover never hits the IoT, so the synthetic event from PostClassCheck
        // is the valid proof that the key was handed off to this schedule.
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

        if ($wasUsed) {
            $runAt = $this->schedule->getPostClassCheckRunAt(10);
            if ($runAt->isFuture()) {
                PostClassCheckJob::dispatch($this->schedule)->delay($runAt);
            }

            return;
        }

        // No USED event at all — the key was never used for this schedule.
        // Expire it. The PostClassCheck from the previous schedule would have
        // created a synthetic event if it assumed a handoff, so reaching here
        // means no handoff was assumed either.
        $this->schedule->expire();
    }
}
