<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;

class ClassroomCalendar extends Component
{
    
    #[Title('Calendar')]
    public function render()
    {
        // Sample data for 9 rooms
        $rooms = [
            ['id' => 'room-300', 'title' => 'Room 300'],
            ['id' => 'room-301', 'title' => 'Room 301'],
            ['id' => 'room-302', 'title' => 'Room 302'],
            ['id' => 'room-303', 'title' => 'Room 303'],
            ['id' => 'room-304', 'title' => 'Room 304'],
            ['id' => 'room-305', 'title' => 'Room 305'],
            ['id' => 'room-306', 'title' => 'Room 306'],
            ['id' => 'room-307', 'title' => 'Room 307'],
            ['id' => 'room-308', 'title' => 'Room 308'],
        ];

        // Sample events/reservations
        $events = [
            [
                'id' => '1',
                'resourceId' => 'room-300',
                'title' => 'Mathematics 101',
                'start' => now()->setTime(9, 0)->toIso8601String(),
                'end' => now()->setTime(10, 30)->toIso8601String(),
                'backgroundColor' => '#3b82f6',
            ],
            [
                'id' => '2',
                'resourceId' => 'room-301',
                'title' => 'Physics Lab',
                'start' => now()->setTime(10, 0)->toIso8601String(),
                'end' => now()->setTime(11, 30)->toIso8601String(),
                'backgroundColor' => '#10b981',
            ],
            [
                'id' => '3',
                'resourceId' => 'room-302',
                'title' => 'Chemistry 201',
                'start' => now()->setTime(13, 0)->toIso8601String(),
                'end' => now()->setTime(14, 30)->toIso8601String(),
                'backgroundColor' => '#f59e0b',
            ],
            [
                'id' => '4',
                'resourceId' => 'room-303',
                'title' => 'Computer Science',
                'start' => now()->setTime(14, 0)->toIso8601String(),
                'end' => now()->setTime(15, 30)->toIso8601String(),
                'backgroundColor' => '#8b5cf6',
            ],
            [
                'id' => '5',
                'resourceId' => 'room-304',
                'title' => 'English Literature',
                'start' => now()->setTime(9, 30)->toIso8601String(),
                'end' => now()->setTime(11, 0)->toIso8601String(),
                'backgroundColor' => '#ec4899',
            ],
            [
                'id' => '6',
                'resourceId' => 'room-305',
                'title' => 'History Seminar',
                'start' => now()->setTime(11, 0)->toIso8601String(),
                'end' => now()->setTime(12, 30)->toIso8601String(),
                'backgroundColor' => '#14b8a6',
            ],
            [
                'id' => '7',
                'resourceId' => 'room-306',
                'title' => 'Biology Lab',
                'start' => now()->setTime(13, 30)->toIso8601String(),
                'end' => now()->setTime(15, 0)->toIso8601String(),
                'backgroundColor' => '#06b6d4',
            ],
            [
                'id' => '8',
                'resourceId' => 'room-307',
                'title' => 'Art Workshop',
                'start' => now()->setTime(10, 30)->toIso8601String(),
                'end' => now()->setTime(12, 0)->toIso8601String(),
                'backgroundColor' => '#f97316',
            ],
            [
                'id' => '9',
                'resourceId' => 'room-308',
                'title' => 'Music Theory',
                'start' => now()->setTime(14, 30)->toIso8601String(),
                'end' => now()->setTime(16, 0)->toIso8601String(),
                'backgroundColor' => '#84cc16',
            ],
        ];

        return view('livewire.classroom-calendar', [
            'rooms' => $rooms,
            'events' => $events,
        ]);
    }
}



