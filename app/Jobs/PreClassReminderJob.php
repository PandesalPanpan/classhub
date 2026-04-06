<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\Schedule;
use App\Models\Schedule as ScheduleModel;
use App\ScheduleStatus;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PreClassReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function handle(): void
    {
        $this->schedule->refresh();
        $this->schedule->load('room.key');

        if ($this->schedule->status !== ScheduleStatus::Approved) {
            return;
        }

        $key = $this->schedule->room->key ?? null;
        if (! $key) {
            return;
        }

        $key->refresh();

        if ($key->status === KeyStatus::Disabled) {
            return;
        }

        if ($key->status === KeyStatus::Stored) {
            // Key is ready — check if there's a NEXT schedule for handover hint
            $nextSchedule = $this->findNextScheduleInHandoverWindow();

            EmailNotificationService::sendKeyReadyForPickup(
                $this->schedule,
                $nextSchedule
            );

            return;
        }

        if ($key->status === KeyStatus::Used) {
            // Key is out — find who has it
            $previousSchedule = $this->findPreviousScheduleInHandoverWindow();

            if ($previousSchedule) {
                EmailNotificationService::sendKeyWithPreviousUser(
                    $this->schedule,
                    $previousSchedule
                );
            } else {
                // Fallback — key is used but no previous schedule found
                // (e.g., manual issuance, or event history is incomplete)
                EmailNotificationService::sendKeyReadyForPickup($this->schedule, null);
            }

            return;
        }

        if ($key->status === KeyStatus::Missing) {
            EmailNotificationService::sendKeyMissingReminder($this->schedule);
        }
    }

    /**
     * Find the next approved schedule in the same room that starts within the handover window.
     */
    protected function findNextScheduleInHandoverWindow(): ?Schedule
    {
        $windowEnd = Carbon::instance($this->schedule->end_time)
            ->addMinutes(PostClassCheckJob::HANDOVER_WINDOW_MINUTES);

        return Schedule::query()
            ->where('room_id', $this->schedule->room_id)
            ->where('id', '!=', $this->schedule->id)
            ->where('status', ScheduleStatus::Approved)
            ->where('start_time', '>', $this->schedule->end_time)
            ->where('start_time', '<=', $windowEnd)
            ->orderBy('start_time')
            ->first();
    }

    /**
     * Find the previous approved schedule in the same room whose end_time falls
     * within the handover window before this schedule's start.
     */
    protected function findPreviousScheduleInHandoverWindow(): ?Schedule
    {
        $handoverStart = Carbon::instance($this->schedule->start_time)
            ->subMinutes(PostClassCheckJob::HANDOVER_WINDOW_MINUTES);

        return Schedule::query()
            ->where('room_id', $this->schedule->room_id)
            ->where('id', '!=', $this->schedule->id)
            ->where('status', ScheduleStatus::Approved)
            ->where('end_time', '>=', $handoverStart)
            ->where('end_time', '<', $this->schedule->start_time)
            ->orderByDesc('end_time')
            ->first();
    }
}
