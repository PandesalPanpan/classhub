<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Livewire\Component;
use Livewire\Attributes\Title;

class PublicCalendar extends Component
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
                    'title' => $schedule->event_title,
                    'start' => $schedule->start_time->toIso8601String(),
                    'end' => $schedule->end_time->toIso8601String(),
                ];
            })
            ->toArray();

        return view('livewire.public-calendar', [
            'rooms' => $rooms,
            'events' => $events,
        ]);
    }
}
