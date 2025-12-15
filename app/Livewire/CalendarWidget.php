<?php

namespace App\Livewire;

use App\Models\Room;
use App\Models\Schedule;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Schedule::class;
    public ?string $filterRoom = null;
    
    protected ?\Illuminate\Support\Collection $roomsCache = null;
    protected ?string $roomsCacheFilter = null;

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
            CreateAction::make()
                ->mountUsing(function ($form, array $arguments) {
                    // Pre-fill start_time and end_time when a date selection is made
                    if (isset($arguments['type']) && $arguments['type'] === 'select') {
                        $form->fill([
                            'start_time' => $arguments['start'] ?? null,
                            'end_time' => $arguments['end'] ?? null,
                        ]);
                    }
                }),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            DateTimePicker::make('start_time')
                ->required(),
            DateTimePicker::make('end_time')
                ->required(),
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

    protected function getRooms(): \Illuminate\Support\Collection
    {
        // Check if cache is valid (exists and filter hasn't changed)
        if ($this->roomsCache !== null && $this->roomsCacheFilter === $this->filterRoom) {
            return $this->roomsCache;
        }

        $query = Room::query();

        if ($this->filterRoom) {
            $roomNumber = str_replace('room-', '', $this->filterRoom);
            $query->where('room_number', $roomNumber);
        }

        $this->roomsCache = $query->get()->keyBy('id');
        $this->roomsCacheFilter = $this->filterRoom;
        return $this->roomsCache;
    }

    protected function getResources(): array
    {
        return $this->getRooms()
            ->map(fn($room) => [
                'id' => "room-{$room->room_number}",
                'title' => $room->room_number,
            ])
            ->values()
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
        // Get rooms once (cached) for mapping
        $rooms = $this->getRooms();
        
        // Build a single query for approved schedules and pending requests by logged-in user
        $query = Schedule::query()
            ->where(function ($q) {
                // Always fetch approved schedules
                $q->where('status', \App\ScheduleStatus::Approved);

                // In app panel, also fetch pending requests made by the logged-in user
                if ($this->isAppPanel() && Auth::check()) {
                    $q->orWhere(function ($pendingQ) {
                        $pendingQ->where('status', \App\ScheduleStatus::Pending)
                            ->where('requester_id', Auth::id());
                    });
                }
            });

        // Apply room filter if set - filter by room_id instead of whereHas for better performance
        if ($this->filterRoom) {
            $roomNumber = str_replace('room-', '', $this->filterRoom);
            $room = $rooms->firstWhere('room_number', $roomNumber);
            if ($room) {
                $query->where('room_id', $room->id);
            } else {
                // If room not found, return empty array
                return [];
            }
        }

        $schedules = $query->get();

        // Map schedules to calendar events using pre-fetched rooms
        return $schedules->map(function ($schedule) use ($rooms) {
            $room = $rooms->get($schedule->room_id);
            
            // Skip if room not found (shouldn't happen, but safety check)
            if (!$room) {
                return null;
            }

            $color = $this->hashTitleToColor($schedule->title ?? '');
            $isPending = $schedule->status === \App\ScheduleStatus::Pending;

            return [
                'id' => $schedule->id,
                'resourceId' => "room-{$room->room_number}",
                'title' => $isPending ? $schedule->title . ' (Pending)' : $schedule->title,
                'start' => $schedule->start_time->toIso8601String(),
                'end' => $schedule->end_time->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $isPending ? '#f59e0b' : $color, // amber-500 for pending
                'borderWidth' => $isPending ? 3 : 1,
                'classNames' => $isPending ? ['pending-request'] : [],
            ];
        })->filter()->values()->toArray();
    }

    protected function getCurrentPanelId(): ?string
    {
        return Filament::getCurrentPanel()?->getId();
    }

    protected function isAdminPanel(): bool
    {
        return $this->getCurrentPanelId() === 'admin';
    }

    protected function isAppPanel(): bool
    {
        return $this->getCurrentPanelId() === 'app';
    }
}
