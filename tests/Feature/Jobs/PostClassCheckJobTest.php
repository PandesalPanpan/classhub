<?php

namespace Tests\Feature\Jobs;

use App\Jobs\PostClassCheckJob;
use App\KeyStatus;
use App\Mail\Key\KeyMissing;
use App\Mail\Schedule\HandoverKeyMissingRequester;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostClassCheckJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_nothing_when_key_returned(): void
    {
        Mail::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();

        KeyEvent::factory()->stored()->create([
            'key_id' => $key->id,
            'schedule_id' => $schedule->id,
            'occurred_at' => $schedule->end_time->copy()->addMinute(),
        ]);

        (new PostClassCheckJob($schedule))->handle();

        $this->assertNotSame(KeyStatus::Missing, $key->fresh()->status);
    }

    public function test_finalizes_pending_handover_when_key_returned(): void
    {
        [$schedule, $key] = $this->makeEndedScheduleWithKey();

        $next = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->end_time->copy()->addMinutes(5),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        $handover = ScheduleHandover::factory()->create([
            'previous_schedule_id' => $schedule->id,
            'next_schedule_id' => $next->id,
        ]);

        KeyEvent::factory()->stored()->create([
            'key_id' => $key->id,
            'schedule_id' => $schedule->id,
            'occurred_at' => $schedule->end_time->copy()->addMinute(),
        ]);

        (new PostClassCheckJob($schedule))->handle();

        $this->assertNotNull($handover->fresh()->resolution_finalized_at);
    }

    public function test_applies_handover_when_both_confirmed(): void
    {
        [$schedule] = $this->makeEndedScheduleWithKey();

        $next = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => now()->addMinutes(10),
            'end_time' => now()->addHours(2),
        ]);

        $handover = ScheduleHandover::factory()->bothConfirmed()->create([
            'previous_schedule_id' => $schedule->id,
            'next_schedule_id' => $next->id,
        ]);

        (new PostClassCheckJob($schedule))->handle();

        $this->assertNotNull($handover->fresh()->resolution_finalized_at);
        $this->assertDatabaseHas('key_events', [
            'schedule_id' => $next->id,
            'status' => KeyStatus::Used->value,
            'source' => 'synthetic',
        ]);
    }

    public function test_marks_key_missing_when_handover_not_confirmed(): void
    {
        Mail::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();

        $next = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->end_time->copy()->addMinutes(5),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        ScheduleHandover::factory()->create([
            'previous_schedule_id' => $schedule->id,
            'next_schedule_id' => $next->id,
        ]);

        $this->seedAdmin();

        (new PostClassCheckJob($schedule))->handle();

        $this->assertSame(KeyStatus::Missing, $key->fresh()->status);
    }

    public function test_marks_key_missing_when_no_handover_exists(): void
    {
        Mail::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();
        $this->seedAdmin();

        (new PostClassCheckJob($schedule))->handle();

        $this->assertSame(KeyStatus::Missing, $key->fresh()->status);
    }

    public function test_sends_missing_emails_to_admin_and_requester(): void
    {
        Mail::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();
        $this->seedAdmin();

        (new PostClassCheckJob($schedule))->handle();

        Mail::assertQueued(KeyMissing::class);
        Mail::assertQueued(HandoverKeyMissingRequester::class);
    }

    private function seedAdmin(): void
    {
        Role::findOrCreate('Admin');
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('Admin');
    }

    private function makeEndedScheduleWithKey(): array
    {
        $room = Room::factory()->create();
        $schedule = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinutes(20),
        ]);
        $key = Key::factory()->create([
            'room_id' => $room->id,
            'status' => KeyStatus::Used,
        ]);

        return [$schedule, $key];
    }
}
