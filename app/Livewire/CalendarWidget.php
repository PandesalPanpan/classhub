<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public ?string $filterRoom = null;

    protected function headerActions(): array
    {
        $roomNumber = $this->filterRoom ? str_replace('room-', '', $this->filterRoom) : null;
        $label = $roomNumber ? "Room: {$roomNumber}" : 'Filter by Room';

        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('filament-fullcalendar--refresh');
                }),
            Action::make('filterRoom')
                ->label($label)
                ->icon('heroicon-o-funnel')
                ->color($roomNumber ? 'primary' : 'gray')
                ->badge($roomNumber ? null : 'All')
                ->schema([
                    Select::make('filterRoom')
                        ->label('Room')
                        ->placeholder('All Rooms')
                        ->options(function () {
                            return Room::query()
                                ->orderBy('room_number')
                                ->pluck('room_number', 'room_number')
                                ->mapWithKeys(fn($roomNumber) => ["room-{$roomNumber}" => $roomNumber])
                                ->toArray();
                        })
                        ->searchable()
                        ->default($this->filterRoom),
                ])
                ->action(function (array $data) {
                    $this->filterRoom = $data['filterRoom'] ?? null;
                    $this->dispatch('filament-fullcalendar--refresh');
                }),
            ...parent::headerActions(),
        ];
    }

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
        $query = Room::query();

        if ($this->filterRoom) {
            $roomNumber = str_replace('room-', '', $this->filterRoom);
            $query->where('room_number', $roomNumber);
        }

        return $query
            ->get()
            ->map(fn($room) => [
                'id' => "room-{$room->room_number}",
                'title' => $room->room_number,
            ])
            ->toArray();
    }

    protected function getColorPalette(): array
    {
        return [
            '#2563eb', // blue-600
            '#7c3aed', // violet-600
            '#0891b2', // cyan-600
            '#16a34a', // green-600
            '#d97706', // amber-600
            '#dc2626', // red-600
            '#0ea5e9', // sky-500
            '#9333ea', // purple-600
        ];
    }

    protected function hashTitleToColor(string $title): string
    {
        $palette = $this->getColorPalette();
        $hash = 0;
        
        // djb2 hash algorithm (same as JavaScript)
        for ($i = 0; $i < strlen($title); $i++) {
            $hash = (($hash << 5) - $hash + ord($title[$i])) & 0x7FFFFFFF;
        }
        
        $idx = abs($hash) % count($palette);
        return $palette[$idx];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $query = Schedule::where('status', \App\ScheduleStatus::Approved)
            ->with('room');

        if ($this->filterRoom) {
            $roomNumber = str_replace('room-', '', $this->filterRoom);
            $query->whereHas('room', function ($q) use ($roomNumber) {
                $q->where('room_number', $roomNumber);
            });
        }

        return $query
            ->get()
            ->map(function ($schedule) {
                $color = $this->hashTitleToColor($schedule->title ?? '');
                
                return [
                    'id' => $schedule->id,
                    'resourceId' => "room-{$schedule->room->room_number}",
                    'title' => $schedule->title,
                    'start' => $schedule->start_time->toIso8601String(),
                    'end' => $schedule->end_time->toIso8601String(),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                ];
            })
            ->toArray();
    }
}
