<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\Setting;
use App\ScheduleStatus;
use App\ScheduleType;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EndOfClassJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("end_of_class:{$this->schedule->id}"),
        ];
    }

    public function uniqueId(): string
    {
        return "end_of_class:{$this->schedule->id}";
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

        if (in_array($key->status, [KeyStatus::Disabled, KeyStatus::Missing], true)) {
            return;
        }

        // STORED at/after class start (not only end) detects early returns before class ends.
        $wasReturned = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Stored->value)
            ->where('occurred_at', '>=', $this->schedule->start_time)
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

        $existingHandover = ScheduleHandover::query()
            ->where('previous_schedule_id', $this->schedule->id)
            ->whereNull('resolution_finalized_at')
            ->where('next_schedule_id', '!=', $nextSchedule->id)
            ->first();

        if ($existingHandover) {
            $oldNextScheduleId = $existingHandover->next_schedule_id;

            $existingHandover->update([
                'next_schedule_id' => $nextSchedule->id,
            ]);

            Log::info('EndOfClassJob: Corrected stale handover', [
                'schedule_id' => $this->schedule->id,
                'old_next_schedule_id' => $oldNextScheduleId,
                'new_next_schedule_id' => $nextSchedule->id,
            ]);
        }

        if ($nextSchedule->requester_id === $this->schedule->requester_id) {
            $key->update(['status' => KeyStatus::HandedOver]);

            KeyEvent::firstOrCreate(
                [
                    'key_id' => $key->id,
                    'schedule_id' => $nextSchedule->id,
                    'status' => KeyStatus::Used->value,
                    'source' => 'synthetic',
                ],
                [
                    'occurred_at' => $nextSchedule->start_time,
                ]
            );

            Log::info('EndOfClassJob: Same requester back-to-back schedules, skipping handover confirmation emails', [
                'schedule_id' => $this->schedule->id,
                'next_schedule_id' => $nextSchedule->id,
                'requester_id' => $this->schedule->requester_id,
            ]);

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

        if (! $handover->wasRecentlyCreated) {
            Log::info('EndOfClassJob: Handover already exists, skipping duplicate email send', [
                'schedule_id' => $this->schedule->id,
                'handover_id' => $handover->id,
            ]);

            $this->dispatchPostClassCheck();

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
            ->where('type', '!=', ScheduleType::Template)
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
