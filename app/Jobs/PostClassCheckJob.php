<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\ScheduleStatus;
use App\Services\EmailNotificationService;
use App\Services\HandoverOperationalService;
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

    public function __construct(
        public Schedule $schedule
    ) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("post_class_check:{$this->schedule->id}"),
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

        // Step 1: Was the key returned? Check for a STORED event at/after class ended.
        $wasReturned = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Stored->value)
            ->where('occurred_at', '>=', $this->schedule->end_time)
            ->exists();

        if ($wasReturned) {
            Log::info('PostClassCheckJob: Key was returned to box', [
                'schedule_id' => $this->schedule->id,
            ]);

            // If a pending handover existed for this schedule, finalize it cleanly
            // (key came back — no missing, no operational handover needed).
            $handover = ScheduleHandover::where('previous_schedule_id', $this->schedule->id)
                ->whereNull('resolution_finalized_at')
                ->first();

            if ($handover) {
                $handover->markFinalized();
                Log::info('PostClassCheckJob: Finalized pending handover (key returned)', [
                    'handover_id' => $handover->id,
                ]);
            }

            return;
        }

        // If this handover was already applied through early confirmation,
        // there is nothing left for post-class to resolve.
        $alreadyAppliedHandover = ScheduleHandover::where('previous_schedule_id', $this->schedule->id)
            ->whereNotNull('resolution_finalized_at')
            ->first();

        if ($alreadyAppliedHandover && $alreadyAppliedHandover->isBothConfirmed()) {
            Log::info('PostClassCheckJob: Handover already applied via early confirmation', [
                'schedule_id' => $this->schedule->id,
                'handover_id' => $alreadyAppliedHandover->id,
            ]);

            return;
        }

        // Step 2: Key not returned — check for an existing pending handover.
        $handover = ScheduleHandover::where('previous_schedule_id', $this->schedule->id)
            ->whereNull('resolution_finalized_at')
            ->with(['nextSchedule'])
            ->first();

        if ($handover) {
            $this->resolveHandover($handover, $key);
        } else {
            // No handover was offered (EndOfClassJob found no eligible next
            // schedule, or was never dispatched). Key is simply missing.
            $this->markKeyMissing($key);
        }
    }

    /**
     * Resolve a pending handover: apply if both confirmed, otherwise mark missing.
     */
    private function resolveHandover(ScheduleHandover $handover, Key $key): void
    {
        if ($handover->isBothConfirmed()) {
            Log::info('PostClassCheckJob: Both parties confirmed — applying operational handover', [
                'schedule_id' => $this->schedule->id,
                'handover_id' => $handover->id,
            ]);

            HandoverOperationalService::apply($handover);

            return;
        }

        // Not both confirmed (could be neither, or only one, or a dispute) → missing.
        Log::warning('PostClassCheckJob: Handover not fully confirmed — marking key missing', [
            'schedule_id' => $this->schedule->id,
            'handover_id' => $handover->id,
            'previous_confirmed_at' => $handover->previous_confirmed_at,
            'next_confirmed_at' => $handover->next_confirmed_at,
            'previous_disputed_at' => $handover->previous_disputed_at,
            'next_disputed_at' => $handover->next_disputed_at,
        ]);

        $handover->markFinalized();
        $this->markKeyMissing($key);
    }

    /**
     * Mark the key as missing, notify admins, and send an urgent notice to
     * the schedule's requester to return the key immediately.
     */
    private function markKeyMissing(Key $key): void
    {
        $key->update(['status' => KeyStatus::Missing]);

        Log::warning('PostClassCheckJob: Key marked as MISSING', [
            'schedule_id' => $this->schedule->id,
            'key_id' => $key->id,
        ]);

        EmailNotificationService::sendKeyMissing($this->schedule);
        EmailNotificationService::sendHandoverKeyMissingToRequester($this->schedule);
    }
}
