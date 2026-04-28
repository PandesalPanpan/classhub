<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EndOfClassJob;
use App\Jobs\PostClassCheckJob;
use App\KeyStatus;
use App\Mail\Schedule\HandoverConfirmationRequested;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EndOfClassJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_nothing_when_key_already_returned(): void
    {
        Queue::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();

        KeyEvent::factory()->stored()->create([
            'key_id' => $key->id,
            'schedule_id' => $schedule->id,
            'occurred_at' => $schedule->end_time->copy()->addMinute(),
        ]);

        (new EndOfClassJob($schedule))->handle();

        Queue::assertNothingPushed();
    }

    public function test_creates_handover_and_emails_when_next_schedule_exists(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();

        $nextSchedule = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->end_time->copy()->addMinutes(10),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        (new EndOfClassJob($schedule))->handle();

        $this->assertDatabaseHas('schedule_handovers', [
            'previous_schedule_id' => $schedule->id,
            'next_schedule_id' => $nextSchedule->id,
        ]);
        $this->assertCount(2, Mail::queued(HandoverConfirmationRequested::class));
    }

    public function test_dispatches_post_class_check_when_no_next_schedule(): void
    {
        Queue::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();

        (new EndOfClassJob($schedule))->handle();

        Queue::assertPushed(PostClassCheckJob::class, 1);
    }

    public function test_dispatches_post_class_check_when_next_schedule_exists(): void
    {
        Queue::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();

        Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->end_time->copy()->addMinutes(5),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        (new EndOfClassJob($schedule))->handle();

        Queue::assertPushed(PostClassCheckJob::class, 1);
    }

    public function test_skips_disabled_keys(): void
    {
        Queue::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();
        $key->update(['status' => KeyStatus::Disabled]);

        (new EndOfClassJob($schedule))->handle();

        Queue::assertNothingPushed();
    }

    public function test_detects_stored_event_after_start_even_if_before_scheduled_end(): void
    {
        Queue::fake();

        [$schedule, $key] = $this->makeEndedScheduleWithKey();

        KeyEvent::factory()->stored()->create([
            'key_id' => $key->id,
            'schedule_id' => $schedule->id,
            'occurred_at' => $schedule->start_time->copy()->addMinutes(30),
        ]);

        (new EndOfClassJob($schedule))->handle();

        Queue::assertNotPushed(PostClassCheckJob::class);
    }

    public function test_same_requester_back_to_back_does_not_dispatch_post_class_check(): void
    {
        Mail::fake();
        Queue::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();

        Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'requester_id' => $schedule->requester_id,
            'start_time' => $schedule->end_time->copy(),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        (new EndOfClassJob($schedule))->handle();

        Queue::assertNotPushed(PostClassCheckJob::class);
    }

    public function test_does_not_duplicate_handover_records(): void
    {
        Queue::fake();

        [$schedule] = $this->makeEndedScheduleWithKey();
        $next = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => $schedule->end_time->copy()->addMinutes(5),
            'end_time' => $schedule->end_time->copy()->addHour(),
        ]);

        ScheduleHandover::factory()->create([
            'previous_schedule_id' => $schedule->id,
            'next_schedule_id' => $next->id,
            'resolution_finalized_at' => null,
        ]);

        (new EndOfClassJob($schedule))->handle();

        $this->assertSame(1, ScheduleHandover::where('previous_schedule_id', $schedule->id)->count());
    }

    private function makeEndedScheduleWithKey(): array
    {
        $room = Room::factory()->create();
        $schedule = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinutes(5),
        ]);
        $key = Key::factory()->create([
            'room_id' => $room->id,
            'status' => KeyStatus::Used,
        ]);

        return [$schedule, $key];
    }
}
