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

    public function __construct(
        public Schedule $schedule
    ) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("verify_key_usage:{$this->schedule->id}"),
        ];
    }

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
        // A synthetic event created by HandoverOperationalService is valid proof.
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
            // Queue end-of-class evaluation at exactly schedule end.
            $endOfClassRunAt = $this->schedule->end_time;
            if ($endOfClassRunAt->isFuture()) {
                EndOfClassJob::dispatch($this->schedule)->delay($endOfClassRunAt);
            } else {
                EndOfClassJob::dispatch($this->schedule);
            }

            return;
        }

        // No USED event — key was never used for this schedule; expire it.
        $this->schedule->expire();
    }
}
