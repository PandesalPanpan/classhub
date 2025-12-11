<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Livewire\Component;
use Livewire\Attributes\Title;

class ClassroomCalendar extends Component
{

    #[Title('Calendar')]
    public function render()
    {
        $rooms = Room::query()
            ->get()
            ->map(fn($room) => [
                'id' => "room-{$room->room_number}",
                'title' => $room->room_number,
            ])
            ->toArray();

        // Fetch approved/active schedules and format for FullCalendar
        $events = Schedule::where('status', \App\ScheduleStatus::Approved)
            ->with('room')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'resourceId' => "room-{$schedule->room->room_number}",
                    'title' => $schedule->title,
                    'start' => $schedule->start_time->toIso8601String(),
                    'end' => $schedule->end_time->toIso8601String(),
                    'backgroundColor' => '#3b82f6',
                ];
            })
            ->toArray();

        // $events[] = [
        //     'id' => 'room-300',
        //     'resourceId' => 'room-300',
        //     'title' => 'Static Schedule',
        //     'start' => now()->setTime(9, 0)->toIso8601String(),
        //     'end' => now()->setTime(10, 30)->toIso8601String(),
        //     'backgroundColor' => '#3b82f6',
        // ];

        // dd($events);
        return view('livewire.classroom-calendar', [
            'rooms' => $rooms,
            'events' => $events,
        ]);
    }
}
