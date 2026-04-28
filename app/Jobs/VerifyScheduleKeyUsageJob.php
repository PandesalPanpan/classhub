<?php

namespace App\Jobs;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\Setting;
use App\ScheduleStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyScheduleKeyUsageJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const MAX_HANDOVER_DEFER_RETRIES = 3;

    public int $uniqueFor = 3600;

    public function __construct(
        public Schedule $schedule,
        public int $retryCount = 0
    ) {}

    public function middleware(): array
    {
        return [
            new WithoutOverlapping("verify_key_usage:{$this->schedule->id}"),
        ];
    }

    public function uniqueId(): string
    {
        return "verify_key_usage:{$this->schedule->id}";
    }

    public function handle(): void
    {
        Log::info('VerifyScheduleKeyUsageJob: Running', [
            'schedule_id' => $this->schedule->id,
            'retry_count' => $this->retryCount,
        ]);

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
            Log::info('VerifyScheduleKeyUsageJob: Key is disabled or missing, skipping verification', [
                'schedule_id' => $this->schedule->id,
                'key_status' => $key->status->value,
            ]);

            return;
        }

        // Check for ANY USED event — IoT scan OR synthetic handover.
        // A synthetic event created by HandoverOperationalService is valid proof.
        $wasUsed = KeyEvent::where('key_id', $key->id)
            ->where('status', KeyStatus::Used->value)
            ->where(function ($query) {
                $query->where('schedule_id', $this->schedule->id)
                    ->orWhereBetween('occurred_at', [
                        $this->schedule->start_time->copy()->subMinutes((int) Setting::get('early_key_pickup_minutes')),
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

        $pendingHandover = ScheduleHandover::where('next_schedule_id', $this->schedule->id)
            ->whereNull('resolution_finalized_at')
            ->first();

        if ($pendingHandover) {
            if ($this->retryCount < self::MAX_HANDOVER_DEFER_RETRIES) {
                $retryAt = $pendingHandover->resolution_deadline_at?->copy()->addMinute() ?? now()->addMinute();

                Log::info('VerifyScheduleKeyUsageJob: Pending handover found, deferring check', [
                    'schedule_id' => $this->schedule->id,
                    'handover_id' => $pendingHandover->id,
                    'retry_count' => $this->retryCount,
                    'next_retry_count' => $this->retryCount + 1,
                    'retry_at' => $retryAt,
                ]);

                if ($retryAt->isFuture()) {
                    self::dispatch($this->schedule, $this->retryCount + 1)->delay($retryAt);
                } else {
                    self::dispatch($this->schedule, $this->retryCount + 1);
                }

                return;
            }

            Log::warning('VerifyScheduleKeyUsageJob: Max handover deferrals reached, expiring schedule', [
                'schedule_id' => $this->schedule->id,
                'handover_id' => $pendingHandover->id,
                'retry_count' => $this->retryCount,
            ]);
        }

        // No USED event — key was never used for this schedule; expire it.
        $this->schedule->expire();
    }
}
