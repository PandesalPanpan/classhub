<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public function config(): array
    {
        return [
            'resources' => $this->getResources(),
            "slotMinTime" => "06:00:00",
            "slotMaxTime" => "22:00:00",
            "slotDuration" => "00:30:00",
            "height" => "auto",
            "aspectRatio" => 1.8,
            "editable" => false,
            "selectable" => true,
            "selectMirror" => true,
            "dayMaxEvents" => true,
            "weekends" => true,
            "nowIndicator" => true,
            "hiddenDays" => [0],
        ];
    }

    protected function getResources(): array
    {
        return Room::query()
            ->get()
            ->map(fn($room) => [
                'id' => "room-{$room->room_number}",
                'title' => $room->room_number,
            ])
            ->toArray();
    }

    public function fetchEvents(array $fetchInfo): array
    {
        // Fetch approved/active schedules and format for FullCalendar
        return Schedule::where('status', \App\ScheduleStatus::Approved)
            ->with('room')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'resourceId' => "room-{$schedule->room->room_number}",
                    'title' => $schedule->title,
                    'start' => $schedule->start_time->toIso8601String(),
                    'end' => $schedule->end_time->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
