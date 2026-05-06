<?php

namespace Tests\Feature\Models;

use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomCurrentScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_current_key_schedule_excludes_templates(): void
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id]);
        $user = User::factory()->create();

        Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $user->id,
            'status' => ScheduleStatus::Approved,
            'type' => ScheduleType::Template,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $room->refresh();
        $room->unsetRelation('key');
        $room->unsetRelation('currentKeyScheduleLookup');

        $result = $room->getCurrentKeySchedule();
        $this->assertNull($result);
    }

    public function test_get_current_key_schedule_returns_request_type(): void
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id]);
        $user = User::factory()->create();

        $request = Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $user->id,
            'status' => ScheduleStatus::Approved,
            'type' => ScheduleType::Request,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $room->refresh();
        $room->unsetRelation('key');
        $room->unsetRelation('currentKeyScheduleLookup');

        $result = $room->getCurrentKeySchedule();
        $this->assertNotNull($result);
        $this->assertEquals($request->id, $result->id);
    }

    public function test_get_current_key_schedule_prefers_request_over_template(): void
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id]);
        $user = User::factory()->create();

        Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $user->id,
            'status' => ScheduleStatus::Approved,
            'type' => ScheduleType::Template,
            'start_time' => now()->subMinutes(30),
            'end_time' => now()->addHour(),
        ]);

        $request = Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $user->id,
            'status' => ScheduleStatus::Approved,
            'type' => ScheduleType::Request,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $room->refresh();
        $room->unsetRelation('key');
        $room->unsetRelation('currentKeyScheduleLookup');

        $result = $room->getCurrentKeySchedule();
        $this->assertNotNull($result);
        $this->assertEquals($request->id, $result->id);
    }
}
