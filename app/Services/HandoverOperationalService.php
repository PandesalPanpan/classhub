<?php

namespace App\Services;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\ScheduleHandover;
use Illuminate\Support\Facades\Log;

class HandoverOperationalService
{
    /**
     * Apply the confirmed handover: update key state, create a synthetic USED event
     * for the next schedule, then finalize the handover.
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

        $handover->markFinalized();
    }
}
