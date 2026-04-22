<?php

namespace App\Services;

use App\Jobs\PostClassCheckJob;
use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\ScheduleHandover;
use Illuminate\Support\Facades\Log;

class HandoverOperationalService
{
    /**
     * Apply the confirmed handover: update key state, create a synthetic USED event
     * for the next schedule, and chain verification/post-class jobs for the next slot.
     */
    public static function apply(ScheduleHandover $handover): void
    {
        $handover->load(['previousSchedule.room.key', 'nextSchedule']);

        $previousSchedule = $handover->previousSchedule;
        $nextSchedule = $handover->nextSchedule;

        if (! $previousSchedule || ! $nextSchedule) {
            Log::warning('HandoverOperationalService: Missing schedules on handover', [
                'handover_id' => $handover->id,
            ]);

            return;
        }

        $key = $previousSchedule->room?->key;

        if (! $key) {
            Log::warning('HandoverOperationalService: No key found for previous schedule room', [
                'handover_id' => $handover->id,
                'schedule_id' => $previousSchedule->id,
            ]);

            return;
        }

        $key->update(['status' => KeyStatus::HandedOver]);

        // Create the synthetic USED event so VerifyScheduleKeyUsageJob for B
        // has evidence the key was handed over.
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

        Log::info('HandoverOperationalService: Key handed over, synthetic event created', [
            'handover_id' => $handover->id,
            'key_id' => $key->id,
            'next_schedule_id' => $nextSchedule->id,
        ]);

        // Chain VerifyScheduleKeyUsageJob for B (if still in the future).
        $verifyRunAt = $nextSchedule->getFortyPercentDurationPoint();
        if ($verifyRunAt->isFuture()) {
            VerifyScheduleKeyUsageJob::dispatch($nextSchedule)->delay($verifyRunAt);
        } else {
            VerifyScheduleKeyUsageJob::dispatch($nextSchedule);
        }

        // Chain PostClassCheckJob for B.
        $postClassRunAt = $nextSchedule->getPostClassCheckRunAt();
        if ($postClassRunAt->isFuture()) {
            PostClassCheckJob::dispatch($nextSchedule)->delay($postClassRunAt);
        } else {
            PostClassCheckJob::dispatch($nextSchedule);
        }

        $handover->markFinalized();
    }
}
