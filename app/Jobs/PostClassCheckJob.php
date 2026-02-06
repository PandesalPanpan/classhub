<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\Schedule;
use App\ScheduleStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostClassCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum gap (minutes) between end of current schedule and start of next
     * to allow handover. If gap is larger, key must be returned to box.
     */
    public const HANDOVER_WINDOW_MINUTES = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Schedule $schedule
    ) {}

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
        $key->refresh();

        // Check if key is disabled
        if ($key->status === KeyStatus::Disabled) {
            return;
        }

        // Step 1: Is key in box?
        if ($key->status === KeyStatus::Stored) {
            return;
        }

        // Step 2: Key not in box – check for next schedule in handover window
        $nextSchedule = $this->findNextScheduleInHandoverWindow();

        if (! $nextSchedule) {
            $key->update(['status' => KeyStatus::Missing]);
        } else {
            $key->update(['status' => KeyStatus::HandedOver]);
        }
    }

    /**
     * Find the next approved schedule in the same room that starts within the handover window.
     */
    protected function findNextScheduleInHandoverWindow(): ?Schedule
    {
        $windowEnd = Carbon::instance($this->schedule->end_time)->addMinutes(self::HANDOVER_WINDOW_MINUTES);

        return Schedule::query()
            ->where('room_id', $this->schedule->room_id)
            ->where('id', '!=', $this->schedule->id)
            ->where('status', ScheduleStatus::Approved)
            ->where('start_time', '>', $this->schedule->end_time)
            ->where('start_time', '<=', $windowEnd)
            ->orderBy('start_time')
            ->first();
    }
}
