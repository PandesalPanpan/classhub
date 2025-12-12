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
