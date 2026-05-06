<?php

namespace Tests\Feature\Livewire;

use App\KeyStatus;
use App\Livewire\CalendarWidget;
use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarWidgetForceHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_force_handover_from_previous_schedule_to_current(): void
    {
        Permission::findOrCreate('Update:Schedule');

        $admin = User::factory()->create();
        $admin->givePermissionTo('Update:Schedule');

        Filament::setCurrentPanel('admin');

        $room = Room::factory()->create();
        $key = Key::factory()->create([
            'room_id' => $room->id,
        ]);

        $startA = Carbon::parse('2026-05-06 07:00:00');
        $endA = Carbon::parse('2026-05-06 08:00:00');
        $startB = Carbon::parse('2026-05-06 08:00:00');
        $endB = Carbon::parse('2026-05-06 09:00:00');

        $previous = Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $admin->id,
            'approver_id' => $admin->id,
            'type' => ScheduleType::Request,
            'status' => ScheduleStatus::Approved,
            'start_time' => $startA,
            'end_time' => $endA,
            'subject' => 'Template A Activated',
            'program_year_section' => 'CE-3A',
        ]);

        $next = Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $admin->id,
            'approver_id' => $admin->id,
            'type' => ScheduleType::Request,
            'status' => ScheduleStatus::Approved,
            'start_time' => $startB,
            'end_time' => $endB,
            'subject' => 'Template B Activated',
            'program_year_section' => 'CE-3A',
        ]);

        $this->actingAs($admin);

        Livewire::test(CalendarWidget::class)
            ->call('onEventClick', ['id' => $next->id])
            ->callAction('forceHandover')
            ->assertHasNoActionErrors();

        $handover = ScheduleHandover::query()
            ->where('previous_schedule_id', $previous->id)
            ->where('next_schedule_id', $next->id)
            ->first();

        $this->assertNotNull($handover);
        $this->assertNotNull($handover->previous_confirmed_at);
        $this->assertNotNull($handover->next_confirmed_at);
        $this->assertNotNull($handover->resolution_finalized_at);

        $this->assertDatabaseHas('key_events', [
            'key_id' => $key->id,
            'schedule_id' => $next->id,
            'status' => KeyStatus::Used->value,
            'source' => 'synthetic',
        ]);
    }
}
