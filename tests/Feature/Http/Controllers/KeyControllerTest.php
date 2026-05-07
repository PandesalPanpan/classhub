<?php

namespace Tests\Feature\Http\Controllers;

use App\KeyStatus;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private const IOT_API_KEY = 'test-iot-secret';

    private Key $key;

    private Room $room;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.iot_secret_key' => self::IOT_API_KEY]);
        Permission::findOrCreate('ReceiveKeyNotifications');

        $this->room = Room::factory()->create(['room_number' => '101']);
        $this->key = Key::factory()->create([
            'room_id' => $this->room->id,
            'slot_number' => 'A1',
            'status' => KeyStatus::Stored,
        ]);
        $this->requester = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function putKeyStatus(string $status, array $data = []): TestResponse
    {
        return $this->withHeaders([
            'X-API-Key' => self::IOT_API_KEY,
        ])->putJson('/api/keys/by-slot/A1/status', array_merge(['status' => $status], $data));
    }

    public function test_iot_used_event_links_to_approved_request_schedule(): void
    {
        $schedule = Schedule::factory()->create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::Request,
            'status' => ScheduleStatus::Approved,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'requester_id' => $this->requester->id,
        ]);

        $response = $this->putKeyStatus('USED');

        $response->assertOk();

        $event = KeyEvent::latest()->first();
        $this->assertEquals($schedule->id, $event->schedule_id);
        $this->assertEquals(KeyStatus::Used->value, $event->status);
    }

    public function test_iot_used_event_does_not_link_to_template_schedule(): void
    {
        Schedule::factory()->create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::Template,
            'status' => ScheduleStatus::Approved,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'requester_id' => $this->requester->id,
        ]);

        $response = $this->putKeyStatus('USED');

        $response->assertOk();

        $event = KeyEvent::latest()->first();
        $this->assertNull($event->schedule_id, 'IoT event should not be linked to a template schedule');
    }

    public function test_iot_stored_event_does_not_link_to_template_schedule(): void
    {
        $this->key->update(['status' => KeyStatus::Used]);

        Schedule::factory()->create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::Template,
            'status' => ScheduleStatus::Approved,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'requester_id' => $this->requester->id,
        ]);

        $response = $this->putKeyStatus('STORED');

        $response->assertOk();

        $event = KeyEvent::latest()->first();
        $this->assertNull($event->schedule_id, 'IoT event should not be linked to a template schedule');
    }

    public function test_iot_event_prefers_request_schedule_over_template_when_both_exist(): void
    {
        $requestSchedule = Schedule::factory()->create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::Request,
            'status' => ScheduleStatus::Approved,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'requester_id' => $this->requester->id,
        ]);

        Schedule::factory()->create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::Template,
            'status' => ScheduleStatus::Approved,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addMinutes(50),
            'requester_id' => $this->requester->id,
        ]);

        $response = $this->putKeyStatus('USED');

        $response->assertOk();

        $event = KeyEvent::latest()->first();
        $this->assertEquals($requestSchedule->id, $event->schedule_id);
    }

    public function test_iot_event_has_null_schedule_id_when_no_active_schedule(): void
    {
        $response = $this->putKeyStatus('USED');

        $response->assertOk();

        $event = KeyEvent::latest()->first();
        $this->assertNull($event->schedule_id);
    }
}
