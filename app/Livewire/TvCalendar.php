<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Livewire\Attributes\Title;
use Livewire\Component;

class TvCalendar extends Component
{
    #[Title('TV Calendar')]
    public function render()
    {
        $rooms = Room::query()
            ->get()
            ->map(fn ($room) => [
                'id' => "room-{$room->room_number}",
                'title' => $room->room_number,
            ])
            ->toArray();

        $events = Schedule::where('status', \App\ScheduleStatus::Approved)
            ->with('room')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'resourceId' => "room-{$schedule->room->room_number}",
                    'type' => $schedule->type,
                    'title' => $schedule->event_title,
                    'start' => $schedule->start_time->toIso8601String(),
                    'end' => $schedule->end_time->toIso8601String(),
                ];
            })
            ->toArray();

        return view('livewire.tv-calendar', [
            'rooms' => $rooms,
            'events' => $events,
        ]);
    }
}

