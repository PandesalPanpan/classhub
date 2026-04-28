<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EndOfClassJob;
use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\ScheduleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerifyScheduleKeyUsageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_schedule_when_key_not_used(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule] = $this->makeApprovedScheduleWithKey();

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        $this->assertSame(ScheduleStatus::Expired, $schedule->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_dispatches_end_of_class_job_when_key_used(): void
    {
        Queue::fake();

        [$schedule, $key] = $this->makeApprovedScheduleWithKey();

        KeyEvent::factory()->create([
            'key_id' => $key->id,
            'schedule_id' => $schedule->id,
            'status' => KeyStatus::Used->value,
            'occurred_at' => $schedule->start_time->copy()->addMinutes(10),
        ]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        Queue::assertPushed(EndOfClassJob::class, 1);
    }

    public function test_skips_non_approved_schedules(): void
    {
        Queue::fake();

        [$schedule] = $this->makeApprovedScheduleWithKey();
        $schedule->update(['status' => ScheduleStatus::Pending]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        Queue::assertNothingPushed();
    }

    public function test_skips_schedules_without_key(): void
    {
        Queue::fake();

        $room = Room::factory()->create();
        $schedule = Schedule::factory()->approved()->create(['room_id' => $room->id]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        Queue::assertNothingPushed();
    }

    public function test_skips_when_key_is_missing(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule, $key] = $this->makeApprovedScheduleWithKey();
        $key->update(['status' => KeyStatus::Missing]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        $this->assertSame(ScheduleStatus::Approved, $schedule->fresh()->status);

        Queue::assertNothingPushed();
    }

    public function test_recognizes_synthetic_handover_key_events(): void
    {
        Queue::fake();

        [$schedule, $key] = $this->makeApprovedScheduleWithKey();

        KeyEvent::factory()->synthetic()->create([
            'key_id' => $key->id,
            'schedule_id' => null,
            'status' => KeyStatus::Used->value,
            'occurred_at' => $schedule->start_time->copy()->addMinutes(5),
        ]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        Queue::assertPushed(EndOfClassJob::class, 1);
    }

    public function test_defers_when_pending_handover_exists_for_schedule_as_next(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule] = $this->makeApprovedScheduleWithKey();
        $previous = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->start_time->copy()->subHour(),
            'end_time' => $schedule->start_time,
        ]);

        ScheduleHandover::factory()->create([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $schedule->id,
            'resolution_deadline_at' => now()->addMinutes(10),
            'resolution_finalized_at' => null,
        ]);

        (new VerifyScheduleKeyUsageJob($schedule))->handle();

        $this->assertSame(ScheduleStatus::Approved, $schedule->fresh()->status);
        Queue::assertPushed(VerifyScheduleKeyUsageJob::class, function (VerifyScheduleKeyUsageJob $job) use ($schedule) {
            return $job->schedule->id === $schedule->id
                && $job->retryCount === 1;
        });
    }

    public function test_expires_after_max_retries_even_when_handover_is_pending(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule] = $this->makeApprovedScheduleWithKey();
        $previous = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->start_time->copy()->subHour(),
            'end_time' => $schedule->start_time,
        ]);

        ScheduleHandover::factory()->create([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $schedule->id,
            'resolution_deadline_at' => now()->addMinutes(10),
            'resolution_finalized_at' => null,
        ]);

        (new VerifyScheduleKeyUsageJob($schedule, VerifyScheduleKeyUsageJob::MAX_HANDOVER_DEFER_RETRIES))->handle();

        $this->assertSame(ScheduleStatus::Expired, $schedule->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_logs_execution_with_retry_count(): void
    {
        [$schedule] = $this->makeApprovedScheduleWithKey();

        Log::spy();

        (new VerifyScheduleKeyUsageJob($schedule, 2))->handle();

        Log::shouldHaveReceived('info')
            ->with('VerifyScheduleKeyUsageJob: Running', [
                'schedule_id' => $schedule->id,
                'retry_count' => 2,
            ])
            ->once();
    }

    private function makeApprovedScheduleWithKey(): array
    {
        $room = Room::factory()->create();
        $schedule = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
            'start_time' => now()->subMinutes(40),
            'end_time' => now()->addMinutes(20),
        ]);
        $key = Key::factory()->create([
            'room_id' => $room->id,
            'status' => KeyStatus::Used,
        ]);

        return [$schedule, $key];
    }
}
