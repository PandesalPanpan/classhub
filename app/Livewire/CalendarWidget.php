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
            'resources' => [
                [
                    'id' => 'room-101',
                    'title' => 'Room 101',
                ],
                [
                    'id' => 'room-102',
                    'title' => 'Room 102',
                ],
                [
                    'id' => 'room-103',
                    'title' => 'Room 103',
                ],
            ],
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

    public function fetchEvents(array $fetchInfo): array
    {
        // Return static events for now
        $now = now();
        
        return [
            [
                'id' => '1',
                'resourceId' => 'room-101',
                'title' => 'Test Event 1',
                'start' => $now->copy()->setTime(9, 0)->toIso8601String(),
                'end' => $now->copy()->setTime(10, 30)->toIso8601String(),
            ],
            [
                'id' => '2',
                'resourceId' => 'room-102',
                'title' => 'Test Event 2',
                'start' => $now->copy()->setTime(14, 0)->toIso8601String(),
                'end' => $now->copy()->setTime(15, 30)->toIso8601String(),
            ],
        ];
    }
}
