<?php

namespace App\Livewire;

use App\Filament\Pages\Schemas\OverrideTemplateForm;
use App\Filament\Pages\Schemas\RequestScheduleForm;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\Room;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use App\Services\ScheduleOverlapChecker;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction as FullCalendarViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Schedule::class;

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
            ActionGroup::make([
                Action::make('filterRoomAll')
                    ->label('All Rooms')
                    ->icon($roomNumber ? null : 'heroicon-o-check')
                    ->action(function () {
                        $this->filterRoom = null;
                        $this->dispatch('filament-fullcalendar--refresh');
                    }),
                ...Room::query()
                    ->orderBy('room_number')
                    ->pluck('room_number')
                    ->map(fn (string $roomNum): Action => Action::make("filterRoom_{$roomNum}")
                        ->label($roomNum)
                        ->icon($roomNumber === $roomNum ? 'heroicon-o-check' : null)
                        ->action(function () use ($roomNum) {
                            $this->filterRoom = "room-{$roomNum}";
                            $this->dispatch('filament-fullcalendar--refresh');
                        }))
                    ->values()
                    ->all(),
            ])
                ->label($label)
                ->icon('heroicon-o-funnel')
                ->color($roomNumber ? 'primary' : 'gray')
                ->badge($roomNumber ? null : 'All')
                ->button(),
            CreateAction::make()
                ->authorize(fn () => Auth::check() && Auth::user()->can('Create:Schedule'))
                ->mountUsing(function ($form, array $arguments) {
                    // Pre-fill start_time and end_time when a date selection is made
                    if (isset($arguments['type']) && $arguments['type'] === 'select') {
                        $fillData = [
                            'start_time' => $arguments['start'] ?? null,
                            'end_time' => $arguments['end'] ?? null,
                        ];

                        // Auto-fill room_id based on selected resource or filterRoom
                        $roomId = null;

                        // First, try to get room from the selected resource
                        if (isset($arguments['resource']['id'])) {
                            $resourceId = $arguments['resource']['id'];
                            // Extract room number from resource ID (format: "room-{room_number}")
                            if (str_starts_with($resourceId, 'room-')) {
                                $roomNumber = str_replace('room-', '', $resourceId);
                                $room = Room::where('room_number', $roomNumber)->first();
                                if ($room) {
                                    $roomId = $room->id;
                                }
                            }
                        }

                        // Fallback to filterRoom if no resource was selected
                        if (! $roomId && $this->filterRoom) {
                            $roomNumber = str_replace('room-', '', $this->filterRoom);
                            $room = Room::where('room_number', $roomNumber)->first();
                            if ($room) {
                                $roomId = $room->id;
                            }
                        }

                        if ($roomId) {
                            $fillData['room_id'] = $roomId;
                        }

                        $form->fill($fillData);
                    }
                })
                ->mutateDataUsing(function (array $data): array {
                    // Auto-fill requester_id with currently logged-in user
                    if (Auth::check() && ! isset($data['requester_id'])) {
                        $data['requester_id'] = Auth::id();
                    }

                    // Note: approver_id is typically set when approving/rejecting, not during creation
                    if ($this->isAdminPanel() && Auth::check() && ! isset($data['approver_id'])) {
                        $data['approver_id'] = Auth::id();
                        $data['status'] = ScheduleStatus::Approved;
                    }

                    // Normalize schedule times using duration_minutes (app panel schema)
                    if (isset($data['start_time'], $data['duration_minutes'])) {
                        $start = Carbon::parse($data['start_time']);
                        $data['end_time'] = $start->copy()->addMinutes($data['duration_minutes'])->format('Y-m-d H:i:s');

                        // duration_minutes is only for calculating end_time and should not be stored
                        unset($data['duration_minutes']);
                    }

                    // Overlap validation (Pending + Approved in same room)
                    if (! empty($data['room_id']) && isset($data['start_time'], $data['end_time'])) {
                        if (
                            ScheduleOverlapChecker::hasOverlap(
                                $data['room_id'],
                                Carbon::parse($data['start_time']),
                                Carbon::parse($data['end_time'])
                            )
                        ) {
                            Notification::make()
                                ->title('Schedule conflict')
                                ->body('This room already has a schedule during the selected time.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'start_time' => 'This room already has a schedule during the selected time.',
                            ]);
                        }
                    }

                    return $data;
                }),
            // ViewAction::make()
            //     ->modalHeading('View Schedule')
            //     ->modalSubmitActionLabel('View')
            //     ->modalCancelActionLabel('Cancel')
            //     ->modalWidth('md')
            //     ->action(function (array $data) {
            //         $this->dispatch('filament-fullcalendar--view', $data);
            //     }),
        ];
    }

    public function getFormSchema(): array
    {
        if ($this->isAppPanel()) {
            // Match the app-side "Request Schedule" form when used in the app panel
            return RequestScheduleForm::schema();
        }

        // Admin panel: reuse ScheduleForm schema
        $schema = ScheduleForm::configure(Schema::make());

        return $schema->getComponents();
    }

    public function config(): array
    {
        return [
            'resources' => $this->getResources(),
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '22:00:00',
            'slotDuration' => '00:30:00',
            'height' => 'auto',
            'aspectRatio' => 1.8,
            'editable' => false,
            'selectable' => true,
            'selectMirror' => true,
            'dayMaxEvents' => true,
            'weekends' => true,
            'nowIndicator' => true,
            'hiddenDays' => [0],
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
            ->map(fn ($room) => [
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

        // Hide templates only when an approved override exists (proven).
        // Pending overrides do not hide the template.
        $templateIdsHiddenByOverride = $schedules
            ->whereNotNull('template_id')
            ->where('status', ScheduleStatus::Approved)
            ->pluck('template_id')
            ->unique()
            ->filter()
            ->values();

        // Check if room filter is set once, before mapping
        $hasRoomFilter = (bool) $this->filterRoom;

        // Map schedules to calendar events using pre-fetched rooms
        return $schedules->map(function ($schedule) use ($rooms, $hasRoomFilter, $templateIdsHiddenByOverride) {
            $room = $rooms->get($schedule->room_id);

            // Skip if room not found (shouldn't happen, but safety check)
            if (! $room) {
                return null;
            }

            $isTemplate = $schedule->type === ScheduleType::Template;

            // Hide template only when an approved override exists (proven)
            if ($isTemplate && $templateIdsHiddenByOverride->contains($schedule->id)) {
                return null;
            }
            $isPending = $schedule->status === \App\ScheduleStatus::Pending;

            // Template schedules are "soft" schedules that can be overridden
            // They should be grayed out to indicate they're not final
            if ($isTemplate) {
                $color = '#6b7280'; // gray-500
            } else {
                $color = $this->hashTitleToColor($schedule->subject ?? '');
            }

            // Include room number in title if no room filter is set
            $title = $schedule->event_title;
            if (! $hasRoomFilter) {
                $title = "{$room->room_number} - {$title}";
            }
            if ($isPending) {
                $title = "Pending: {$title}";
            }

            return [
                'id' => $schedule->id,
                'resourceId' => "room-{$room->room_number}",
                'title' => $title,
                'start' => $schedule->start_time->toIso8601String(),
                'end' => $schedule->end_time->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $isPending ? '#f59e0b' : $color, // amber-500 for pending
                'borderWidth' => $isPending ? 3 : 1,
                'classNames' => $isPending ? ['pending-request'] : ($isTemplate ? ['template-schedule'] : []),
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

    protected function viewAction(): Action
    {
        return FullCalendarViewAction::make()
            ->authorize(function (Schedule $record) {
                return Auth::check() && Auth::user()->can('View:Schedule');
            });
    }

    protected function modalActions(): array
    {
        $actions = [];

        $editAction = EditAction::make()
            ->authorize(function (Schedule $record) {
                return Auth::check() && Auth::user()->can('Update:Schedule');
            });
        $actions[] = $editAction;

        $deleteAction = DeleteAction::make()
            ->authorize(function (Schedule $record) {
                return Auth::check() && Auth::user()->can('Delete:Schedule');
            });
        $actions[] = $deleteAction;

        // Add cancel action if request is pending and owned by the user.
        // Name must not be 'cancel' or it is overwritten by the View action's getModalCancelAction().
        $cancelAction = Action::make('cancelRequest')
            ->label('Cancel request')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(function (): bool {
                $record = $this->record;
                if (! $record instanceof Schedule) {
                    return false;
                }

                return $record->status === ScheduleStatus::Pending && $record->requester_id === Auth::id();
            })
            ->modalHeading('Cancel Request')
            ->modalDescription('Are you sure you want to cancel this request? This action cannot be undone.')
            ->modalSubmitActionLabel('Cancel Request')
            ->modalCancelActionLabel('Keep Request')
            ->modalWidth('md')
            ->action(function (): void {
                $record = $this->record;
                if ($record instanceof Schedule) {
                    $record->cancel();
                    $this->unmountAction();
                    $this->refreshRecords();
                }
            });
        $actions[] = $cancelAction;

        $overrideAction = Action::make('override')
            ->label('Request override')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->visible(function (): bool {
                $template = $this->record;
                if (! $template instanceof Schedule || $template->type !== ScheduleType::Template) {
                    return false;
                }
                if (! Auth::check()) {
                    return false;
                }
                $alreadyRequested = Schedule::query()
                    ->where('template_id', $template->id)
                    ->where('requester_id', Auth::id())
                    ->whereIn('status', [ScheduleStatus::Pending, ScheduleStatus::Approved])
                    ->exists();

                return ! $alreadyRequested;
            })
            ->modalHeading('Request override')
            ->modalDescription('Create a prioritized request for this slot. Admins will approve or reject it.')
            ->modalSubmitActionLabel('Request override')
            ->form(OverrideTemplateForm::schema())
            ->mountUsing(function ($form): void {
                $template = $this->record;
                if ($template instanceof Schedule && $template->type === ScheduleType::Template) {
                    $form->fill([
                        'room_id' => $template->room_id,
                        'start_time' => $template->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $template->end_time->format('Y-m-d H:i:s'),
                        'subject' => $template->subject,
                        'program_year_section' => $template->program_year_section,
                        'instructor' => $template->instructor,
                    ]);
                }
            })
            ->action(function (array $data): void {
                $template = $this->record;
                if (! $template instanceof Schedule || $template->type !== ScheduleType::Template) {
                    return;
                }

                $start = Carbon::parse($data['start_time']);
                $end = Carbon::parse($data['end_time']);

                $hasExistingOverride = Schedule::query()
                    ->where('template_id', $template->id)
                    ->whereIn('status', [ScheduleStatus::Pending, ScheduleStatus::Approved])
                    ->where('start_time', '<', $end)
                    ->where('end_time', '>', $start)
                    ->exists();

                if ($hasExistingOverride) {
                    Notification::make()
                        ->title('Override already requested')
                        ->body('This template slot already has a pending or approved override.')
                        ->danger()
                        ->send();

                    return;
                }

                if (
                    ScheduleOverlapChecker::hasOverlap(
                        (int) $data['room_id'],
                        $start,
                        $end,
                        [ScheduleStatus::Approved, ScheduleStatus::Pending],
                        excludeIds: [$template->id]
                    )
                ) {
                    Notification::make()
                        ->title('Schedule conflict')
                        ->body('Another approved or pending schedule exists in this room for the selected time.')
                        ->danger()
                        ->send();

                    return;
                }

                Schedule::create([
                    'room_id' => $data['room_id'],
                    'requester_id' => Auth::id(),
                    'template_id' => $template->id,
                    'is_priority' => true,
                    'type' => ScheduleType::Request,
                    'status' => ScheduleStatus::Pending,
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'subject' => $data['subject'],
                    'program_year_section' => $data['program_year_section'],
                    'instructor' => $data['instructor'] ?? null,
                ]);

                Notification::make()
                    ->title('Override requested')
                    ->body('Your prioritized request has been submitted. Admins will review it.')
                    ->success()
                    ->send();

                $this->unmountAction();
                $this->refreshRecords();
            })
            ->authorize(fn () => Auth::check() && Auth::user()?->can('Create:Schedule'));

        $actions[] = $overrideAction;

        return $actions;
    }
}
