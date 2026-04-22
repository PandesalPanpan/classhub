<?php

namespace Tests\Feature\Services;

use App\Jobs\PostClassCheckJob;
use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Services\HandoverOperationalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HandoverOperationalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_key_status_to_handed_over(): void
    {
        Queue::fake();

        $handover = $this->makeHandoverWithSharedRoom();

        HandoverOperationalService::apply($handover);

        $key = $handover->fresh()->previousSchedule->room->key;
        $this->assertSame(KeyStatus::HandedOver, $key->status);
    }

    public function test_creates_synthetic_used_key_event(): void
    {
        $handover = $this->makeHandoverWithSharedRoom();

        HandoverOperationalService::apply($handover);

        $this->assertDatabaseHas('key_events', [
            'key_id' => $handover->fresh()->previousSchedule->room->key->id,
            'schedule_id' => $handover->next_schedule_id,
            'status' => KeyStatus::Used->value,
            'source' => 'synthetic',
        ]);
    }

    public function test_chains_verify_and_post_class_jobs_for_next_schedule(): void
    {
        Queue::fake();

        $handover = $this->makeHandoverWithSharedRoom();

        HandoverOperationalService::apply($handover);

        Queue::assertPushed(VerifyScheduleKeyUsageJob::class);
        Queue::assertPushed(PostClassCheckJob::class);
    }

    public function test_marks_handover_as_finalized(): void
    {
        $handover = $this->makeHandoverWithSharedRoom();

        HandoverOperationalService::apply($handover);

        $this->assertNotNull($handover->fresh()->resolution_finalized_at);
    }

    private function makeHandoverWithSharedRoom(): ScheduleHandover
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id, 'status' => KeyStatus::Used]);

        $previous = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subHour(),
        ]);

        $next = Schedule::factory()->approved()->create([
            'room_id' => $room->id,
            'start_time' => now()->addMinutes(20),
            'end_time' => now()->addHours(2),
        ]);

        return ScheduleHandover::factory()->create([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $next->id,
        ]);
    }
}
