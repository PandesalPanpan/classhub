<?php

namespace Tests\Feature\Widgets;

use App\Filament\Widgets\ActiveSchedulesWidget;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ActiveSchedulesWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_schedules_widget_excludes_templates(): void
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        Schedule::factory()->create([
            'room_id' => $room->id,
            'requester_id' => $user->id,
            'status' => ScheduleStatus::Approved,
            'type' => ScheduleType::Template,
            'start_time' => now()->subHour(),
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

        $widget = new ActiveSchedulesWidget;
        $method = new ReflectionMethod(ActiveSchedulesWidget::class, 'getTableQuery');
        $query = $method->invoke($widget);
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals($request->id, $results->first()->id);
    }
}
