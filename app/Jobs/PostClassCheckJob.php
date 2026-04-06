<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     * Get the middleware the job should be processed with.
     *
     * Prevents duplicate processing (e.g., on retry) which could cause
     * duplicate synthetic events and email spam.
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping("post_class_check:{$this->schedule->id}"),
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
        $key->refresh();

        if ($key->status === KeyStatus::Disabled) {
            return;
        }

        // Step 1: Was the key returned? Check for a STORED event after class ended.
        $wasReturned = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Stored->value)
            ->where('occurred_at', '>', $this->schedule->end_time)
            ->exists();

        if ($wasReturned) {
            Log::info('PostClassCheckJob: Key was returned to box', [
                'schedule_id' => $this->schedule->id,
            ]);

            return;
        }

        // Step 2: Key not returned — check for next schedule in handover window
        $nextSchedule = $this->findNextScheduleInHandoverWindow();

        if (! $nextSchedule) {
            $key->update(['status' => KeyStatus::Missing]);

            Log::warning('PostClassCheckJob: Key marked as MISSING', [
                'schedule_id' => $this->schedule->id,
                'key_id' => $key->id,
            ]);

            EmailNotificationService::sendKeyMissing($this->schedule);
        } else {
            $key->update(['status' => KeyStatus::HandedOver]);

            Log::info('PostClassCheckJob: Key handed over to next schedule', [
                'from_schedule_id' => $this->schedule->id,
                'to_schedule_id' => $nextSchedule->id,
            ]);

            // Create a synthetic USED event for the next schedule.
            // The IoT never reported this (the key was never put back and taken out again),
            // but the next schedule's VerifyJob needs evidence the key was in use for it.
            KeyEvent::firstOrCreate([
                'key_id' => $key->id,
                'schedule_id' => $nextSchedule->id,
                'status' => KeyStatus::Used->value,
                'source' => 'synthetic',
            ], [
                'occurred_at' => $nextSchedule->start_time,
            ]);

            // Ensure the next schedule gets a PostClassCheckJob.
            // It may already have one from its VerifyJob, but if that job
            // hasn't run yet (or the next schedule was approved late), this
            // guarantees the chain continues.
            $nextRunAt = $nextSchedule->getPostClassCheckRunAt(10);
            if ($nextRunAt->isFuture()) {
                PostClassCheckJob::dispatch($nextSchedule)->delay($nextRunAt);
            }
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
