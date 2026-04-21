<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\Setting;
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

class EndOfClassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("end_of_class:{$this->schedule->id}"),
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
        $key->refresh();

        if ($key->status === KeyStatus::Disabled) {
            return;
        }

        // If key was already returned (STORED event at/after end_time), nothing to hand over.
        $wasReturned = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Stored->value)
            ->where('occurred_at', '>=', $this->schedule->end_time)
            ->exists();

        if ($wasReturned) {
            Log::info('EndOfClassJob: Key already returned, no handover needed', [
                'schedule_id' => $this->schedule->id,
            ]);

            return;
        }

        if (! (bool) Setting::get('handover_enabled')) {
            Log::info('EndOfClassJob: Handover disabled, dispatching PostClassCheckJob', [
                'schedule_id' => $this->schedule->id,
            ]);

            $this->dispatchPostClassCheck();

            return;
        }

        $nextSchedule = $this->findNextScheduleInHandoverWindow();

        if (! $nextSchedule) {
            Log::info('EndOfClassJob: No eligible next schedule in window, dispatching PostClassCheckJob', [
                'schedule_id' => $this->schedule->id,
            ]);

            $this->dispatchPostClassCheck();

            return;
        }

        // Ensure only one handover record per previous schedule (idempotent).
        $handover = ScheduleHandover::firstOrCreate(
            ['previous_schedule_id' => $this->schedule->id],
            [
                'next_schedule_id' => $nextSchedule->id,
                'resolution_deadline_at' => Carbon::instance($this->schedule->end_time)
                    ->addMinutes((int) Setting::get('grace_period_minutes')),
            ]
        );

        if (! $handover->wasRecentlyCreated && $handover->resolution_finalized_at !== null) {
            Log::info('EndOfClassJob: Handover already finalized, skipping', [
                'schedule_id' => $this->schedule->id,
                'handover_id' => $handover->id,
            ]);

            return;
        }

        Log::info('EndOfClassJob: Handover offer created, sending confirmation emails', [
            'schedule_id' => $this->schedule->id,
            'next_schedule_id' => $nextSchedule->id,
            'handover_id' => $handover->id,
        ]);

        EmailNotificationService::sendHandoverConfirmationRequested($handover);
        $this->dispatchPostClassCheck();
    }

    /**
     * Find the next approved schedule in the same room within the handover window.
     * Uses >= so back-to-back slots (A ends 12:00, B starts 12:00) are eligible.
     */
    protected function findNextScheduleInHandoverWindow(): ?Schedule
    {
        $windowEnd = Carbon::instance($this->schedule->end_time)
            ->addMinutes((int) Setting::get('handover_eligibility_window_minutes'));

        return Schedule::query()
            ->where('room_id', $this->schedule->room_id)
            ->where('id', '!=', $this->schedule->id)
            ->where('status', ScheduleStatus::Approved)
            ->where('start_time', '>=', $this->schedule->end_time)
            ->where('start_time', '<=', $windowEnd)
            ->orderBy('start_time')
            ->first();
    }

    private function dispatchPostClassCheck(): void
    {
        $runAt = $this->schedule->getPostClassCheckRunAt();
        if ($runAt->isFuture()) {
            PostClassCheckJob::dispatch($this->schedule)->delay($runAt);

            return;
        }

        PostClassCheckJob::dispatch($this->schedule);
    }
}
