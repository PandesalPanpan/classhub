<?php

namespace Tests\Feature\Models;

use App\Jobs\VerifyScheduleKeyUsageJob;
use App\Mail\Schedule\ScheduleApproved;
use App\Mail\Schedule\ScheduleCancelledConfirmation;
use App\Mail\Schedule\ScheduleExpired;
use App\Mail\Schedule\ScheduleRejected;
use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_sends_email_and_dispatches_key_jobs(): void
    {
        Mail::fake();
        Queue::fake();

        $schedule = $this->makePendingRequestScheduleWithKey();

        $schedule->approve();

        $this->assertSame(ScheduleStatus::Approved, $schedule->fresh()->status);
        Mail::assertQueued(ScheduleApproved::class);
        Queue::assertPushed(VerifyScheduleKeyUsageJob::class);
    }

    public function test_reject_sends_email(): void
    {
        Mail::fake();
        $schedule = $this->makePendingRequestScheduleWithKey();

        $schedule->reject();

        Mail::assertQueued(ScheduleRejected::class);
    }

    public function test_cancel_sends_email(): void
    {
        Mail::fake();
        $schedule = $this->makePendingRequestScheduleWithKey();

        $schedule->cancel();

        Mail::assertQueued(ScheduleCancelledConfirmation::class);
    }

    public function test_cancel_finalizes_pending_handover_when_schedule_is_next_in_chain(): void
    {
        Mail::fake();

        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id]);

        $previous = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
        ]);
        $next = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
        ]);

        $handover = ScheduleHandover::factory()->create([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $next->id,
            'resolution_finalized_at' => null,
        ]);

        $next->cancel();

        $this->assertNotNull($handover->fresh()->resolution_finalized_at);
    }

    public function test_expire_sends_email(): void
    {
        Mail::fake();
        $schedule = $this->makePendingRequestScheduleWithKey();

        $schedule->expire();

        Mail::assertQueued(ScheduleExpired::class);
    }

    public function test_dispatch_key_jobs_skips_when_no_key(): void
    {
        Queue::fake();

        $schedule = Schedule::factory()->approved()->create([
            'room_id' => Room::factory()->create()->id,
        ]);

        $schedule->dispatchKeyJobs();

        Queue::assertNothingPushed();
    }

    public function test_observer_dispatches_key_jobs_on_status_change(): void
    {
        Queue::fake();

        $schedule = $this->makePendingRequestScheduleWithKey();

        $schedule->update(['status' => ScheduleStatus::Approved]);

        Queue::assertPushed(VerifyScheduleKeyUsageJob::class);
    }

    private function makePendingRequestScheduleWithKey(): Schedule
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id]);

        return Schedule::factory()->pending()->create([
            'room_id' => $room->id,
            'requester_id' => User::factory()->create()->id,
            'type' => ScheduleType::Request,
            'start_time' => now()->addMinutes(20),
            'end_time' => now()->addHours(2),
        ]);
    }
}
