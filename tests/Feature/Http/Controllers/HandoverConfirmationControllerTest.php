<?php

namespace Tests\Feature\Http\Controllers;

use App\KeyStatus;
use App\Mail\Key\KeyMissing;
use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HandoverConfirmationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_records_timestamp_for_previous_role(): void
    {
        $handover = $this->makeHandover();
        $url = URL::temporarySignedRoute('handover.confirm', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'previous',
        ]);

        $this->get($url)->assertOk();

        $this->assertNotNull($handover->fresh()->previous_confirmed_at);
    }

    public function test_confirm_records_timestamp_for_next_role(): void
    {
        $handover = $this->makeHandover();
        $url = URL::temporarySignedRoute('handover.confirm', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'next',
        ]);

        $this->get($url)->assertOk();

        $this->assertNotNull($handover->fresh()->next_confirmed_at);
    }

    public function test_confirm_triggers_handover_when_both_confirmed(): void
    {
        Queue::fake();

        $handover = $this->makeHandover(['previous_confirmed_at' => now()->subMinute()]);
        $url = URL::temporarySignedRoute('handover.confirm', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'next',
        ]);

        $this->get($url)->assertOk();

        $this->assertNotNull($handover->fresh()->resolution_finalized_at);
        $key = $handover->fresh()->previousSchedule->room->key;
        $this->assertSame(KeyStatus::HandedOver, $key->status);
    }

    public function test_dispute_records_timestamp_and_alerts_admins(): void
    {
        Mail::fake();
        $this->seedAdmin();

        $handover = $this->makeHandover();
        $url = URL::temporarySignedRoute('handover.dispute', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'previous',
        ]);

        $this->get($url)->assertOk();

        $this->assertNotNull($handover->fresh()->previous_disputed_at);
        Mail::assertQueued(KeyMissing::class);
    }

    public function test_rejects_invalid_role(): void
    {
        $handover = $this->makeHandover();
        $url = URL::temporarySignedRoute('handover.confirm', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'invalid',
        ]);

        $this->get($url)->assertStatus(400);
    }

    public function test_shows_already_resolved_view_when_finalized(): void
    {
        $handover = $this->makeHandover(['resolution_finalized_at' => now()]);
        $url = URL::temporarySignedRoute('handover.confirm', now()->addMinutes(10), [
            'handover' => $handover->id,
            'role' => 'previous',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertViewIs('handover.already-resolved');
    }

    public function test_requires_valid_signature(): void
    {
        $handover = $this->makeHandover();

        $this->get(route('handover.confirm', ['handover' => $handover->id, 'role' => 'previous']))
            ->assertStatus(403);
    }

    private function seedAdmin(): void
    {
        Role::findOrCreate('Admin');
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
    }

    private function makeHandover(array $overrides = []): ScheduleHandover
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
            'start_time' => now()->addMinutes(10),
            'end_time' => now()->addHour(),
        ]);

        return ScheduleHandover::factory()->create(array_merge([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $next->id,
        ], $overrides));
    }
}
