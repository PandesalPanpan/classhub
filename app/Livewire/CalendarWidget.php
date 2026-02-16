<?php

namespace App\Livewire;

use App\Filament\Pages\Schemas\FindAvailableRoomsForm;
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
use Filament\Support\Exceptions\Halt;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public bool $showValidPendingSchedules = false;

    /** @var Collection<int, Schedule>|array<int, Schedule> */
    public Collection|array $matchingPendingSchedules = [];

    /**
     * Results for the Find Available Rooms modal. Each item: room, available, conflicting_schedule.
     *
     * @var array<int, array{room: \App\Models\Room, available: bool, conflicting_schedule: \App\Models\Schedule|null}>
     */
    public array $findAvailableRoomsResults = [];

    protected ?Collection $roomsCache = null;

    protected ?string $roomsCacheFilter = null;

    protected function headerActions(): array
    {
        $roomNumber = $this->filterRoom ? str_replace('room-', '', $this->filterRoom) : null;
        $label = $roomNumber ? "Room: {$roomNumber}" : 'Filter by Room';

        return [
            CreateAction::make()
                ->authorize(fn () => Auth::check() && Auth::user()->can('Create:Schedule'))
                ->mountUsing(function ($form, array $arguments) {
                    $this->matchingPendingSchedules = collect();

                    // Pre-fill start_time and end_time when a date selection is made
                    if (isset($arguments['type']) && $arguments['type'] === 'select') {
                        $fillData = [
                            'start_time' => $arguments['start'] ?? null,
                            'end_time' => $arguments['end'] ?? null,
                        ];

                        // If start_time and end_time is set, calculate duration_minutes
                        // Round to nearest 30 min to match RequestScheduleForm options (30–810)
                        if (isset($fillData['start_time'], $fillData['end_time'])) {
                            $duration = (int) Carbon::parse($fillData['start_time'])
                                ->diffInMinutes(Carbon::parse($fillData['end_time']));
                            $fillData['duration_minutes'] = max(30, min(810, (int) (round($duration / 30) * 30)));
                        }

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

                        // Find pending schedules matching this time (any room) so admin can approve them from the modal.
                        // Do not show any if the selected room already has an approved schedule at this slot.
                        if (isset($fillData['start_time'], $fillData['end_time'])) {
                            $roomId = $fillData['room_id'] ?? null;
                            $hasApprovedInSlot = $roomId !== null && ScheduleOverlapChecker::hasOverlap(
                                (int) $roomId,
                                Carbon::parse($fillData['start_time']),
                                Carbon::parse($fillData['end_time']),
                                [ScheduleStatus::Approved]
                            );
                            $this->matchingPendingSchedules = $hasApprovedInSlot
                                ? collect()
                                : Schedule::query()
                                    ->pendingForTimeSlot($fillData['start_time'], $fillData['end_time'])
                                    ->with(['requester', 'room'])
                                    ->get();
                        }
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
            Action::make('findAvailableRooms')
                ->label('Find rooms')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->visible(fn () => $this->isAdminPanel())
                ->authorize(fn () => Auth::check() && Auth::user()->can('View:Schedule'))
                ->modalHeading('Find available rooms')
                ->modalSubmitActionLabel('Find rooms')
                ->modalWidth('xl')
                ->form(fn () => FindAvailableRoomsForm::schema($this))
                ->mountUsing(function (): void {
                    $this->findAvailableRoomsResults = [];
                    $idx = array_key_last($this->mountedActions);
                    if ($idx !== null) {
                        $actions = $this->mountedActions ?? [];
                        $actions[$idx]['data'] = [
                            'date' => null,
                            'start_time' => null,
                            'duration_minutes' => 60,
                        ];
                        $this->mountedActions = $actions;
                    }
                })
                ->action(function (array $data): void {
                    $date = $data['date'] ?? null;
                    $startTime = $data['start_time'] ?? null;
                    $durationMinutes = isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null;

                    if (! $date || ! $startTime || $durationMinutes === null) {
                        return;
                    }

                    $start = Carbon::parse($date.' '.$startTime);
                    $end = $start->copy()->addMinutes($durationMinutes);

                    $conflictingByRoom = Schedule::query()
                        ->whereIn('status', [ScheduleStatus::Approved, ScheduleStatus::Pending])
                        ->where('start_time', '<', $end->format('Y-m-d H:i:s'))
                        ->where('end_time', '>', $start->format('Y-m-d H:i:s'))
                        ->get(['id', 'room_id', 'subject', 'program_year_section', 'instructor', 'start_time', 'end_time'])
                        ->keyBy('room_id');

                    $rooms = Room::query()
                        ->where('is_active', true)
                        ->orderBy('room_number')
                        ->get();

                    $results = [];
                    foreach ($rooms as $room) {
                        $conflicting = $conflictingByRoom->get($room->id);
                        $results[] = [
                            'room' => $room,
                            'available' => $conflicting === null,
                            'conflicting_schedule' => $conflicting,
                        ];
                    }

                    usort($results, function (array $a, array $b): int {
                        if ($a['available'] !== $b['available']) {
                            return $a['available'] ? -1 : 1;
                        }

                        return strcmp(
                            $a['room']->room_number ?? '',
                            $b['room']->room_number ?? ''
                        );
                    });

                    $this->findAvailableRoomsResults = $results;

                    $idx = array_key_last($this->mountedActions);
                    if ($idx !== null && isset($this->cachedSchemas['mountedActionSchema' . $idx])) {
                        unset($this->cachedSchemas['mountedActionSchema' . $idx]);
                    }

                    throw new Halt;
                }),
                Action::make('showValidPendingSchedules')
                ->label(fn () => $this->showValidPendingSchedules ? 'Hide valid pending' : 'Show valid pending')
                ->icon(fn () => $this->showValidPendingSchedules ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color($this->showValidPendingSchedules ? 'primary' : 'gray')
                ->visible(fn () => $this->isAdminPanel())
                ->action(function () {
                    $this->showValidPendingSchedules = ! $this->showValidPendingSchedules;
                    $this->dispatch('filament-fullcalendar--refresh');
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

        // Schema is built before mountUsing runs, so compute matching pendings from current action args when available
        $matchingPendings = $this->getMatchingPendingSchedulesForSchema();
        $selectedSlotRoomId = $this->getSelectedSlotRoomIdForSchema();
        $schema = ScheduleForm::configure(Schema::make(), $matchingPendings, $selectedSlotRoomId);

        return $schema->getComponents();
    }

    /**
     * Matching pendings for the create/view modal. For create+select we derive from action args;
     * for view we use the viewed record's time and room. Otherwise use property.
     *
     * @return Collection<int, Schedule>|array<int, Schedule>
     */
    protected function getMatchingPendingSchedulesForSchema(): Collection|array
    {
        $lastAction = $this->mountedActions[array_key_last($this->mountedActions ?? []) ?? 0] ?? null;
        $actionName = $lastAction['name'] ?? null;

        if ($actionName === 'view' && isset($this->record) && $this->record instanceof Schedule) {
            $record = $this->record;
            $startTime = $record->start_time instanceof \Carbon\Carbon
                ? $record->start_time->format('Y-m-d H:i:s')
                : $record->start_time;
            $endTime = $record->end_time instanceof \Carbon\Carbon
                ? $record->end_time->format('Y-m-d H:i:s')
                : $record->end_time;
            $roomId = $record->room_id;
            if ($roomId !== null && ScheduleOverlapChecker::hasOverlap(
                (int) $roomId,
                Carbon::parse($startTime),
                Carbon::parse($endTime),
                [ScheduleStatus::Approved]
            )) {
                return [];
            }

            return Schedule::query()
                ->pendingForTimeSlot($startTime, $endTime)
                ->with(['requester', 'room'])
                ->get();
        }

        if ($actionName !== 'create' || ($lastAction['arguments']['type'] ?? null) !== 'select') {
            return $this->matchingPendingSchedules ?? [];
        }

        $arguments = $lastAction['arguments'] ?? [];
        $startTime = $arguments['start'] ?? null;
        $endTime = $arguments['end'] ?? null;
        if (! $startTime || ! $endTime) {
            return $this->matchingPendingSchedules ?? [];
        }

        $selectedRoomId = $this->getSelectedSlotRoomIdForSchema();
        if ($selectedRoomId !== null && ScheduleOverlapChecker::hasOverlap(
            $selectedRoomId,
            Carbon::parse($startTime),
            Carbon::parse($endTime),
            [ScheduleStatus::Approved]
        )) {
            return [];
        }

        return Schedule::query()
            ->pendingForTimeSlot($startTime, $endTime)
            ->with(['requester', 'room'])
            ->get();
    }

    /**
     * Selected slot's room id for the create/view modal (button color and assign-on-approve).
     * Set from create+select action args, or from the viewed record when action is view.
     */
    protected function getSelectedSlotRoomIdForSchema(): ?int
    {
        $lastAction = $this->mountedActions[array_key_last($this->mountedActions ?? []) ?? 0] ?? null;
        $actionName = $lastAction['name'] ?? null;

        if ($actionName === 'view' && isset($this->record) && $this->record instanceof Schedule) {
            return $this->record->room_id;
        }

        if ($actionName !== 'create' || ($lastAction['arguments']['type'] ?? null) !== 'select') {
            return null;
        }

        $arguments = $lastAction['arguments'] ?? [];
        if (isset($arguments['resource']['id']) && str_starts_with((string) $arguments['resource']['id'], 'room-')) {
            $roomNumber = str_replace('room-', '', (string) $arguments['resource']['id']);
            $room = Room::where('room_number', $roomNumber)->first();

            return $room?->id;
        }
        if ($this->filterRoom) {
            $roomNumber = str_replace('room-', '', $this->filterRoom);
            $room = Room::where('room_number', $roomNumber)->first();

            return $room?->id;
        }

        return null;
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

    protected function getRooms(): Collection
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
                $q->where('status', ScheduleStatus::Approved);

                // In app panel, also fetch pending requests made by the logged-in user
                if ($this->isAppPanel() && Auth::check()) {
                    $q->orWhere(function ($pendingQ) {
                        $pendingQ->where('status', ScheduleStatus::Pending)
                            ->where('requester_id', Auth::id());
                    });
                }

                // In admin panel, optionally show valid pending (no approved schedule blocks them)
                if ($this->isAdminPanel() && $this->showValidPendingSchedules) {
                    $q->orWhere(function ($validPendingQ) {
                        $validPendingQ->where('status', ScheduleStatus::Pending)
                            ->whereNotExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('schedules as approved')
                                    ->whereColumn('approved.room_id', 'schedules.room_id')
                                    ->where('approved.status', ScheduleStatus::Approved)
                                    ->where('approved.start_time', '<', DB::raw('schedules.end_time'))
                                    ->where('approved.end_time', '>', DB::raw('schedules.start_time'));
                            });
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
            $isPending = $schedule->status === ScheduleStatus::Pending;

            // Template schedules are "soft" schedules that can be overridden
            // They should be grayed out to indicate they're not final
            if ($isTemplate) {
                $color = '#6b7280'; // gray-500
            } elseif ($isPending) {
                $color = '#ea580c'; // orange-600 – not in getColorPalette(), so pending stands out
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
                'borderColor' => $isPending ? '#ea580c' : $color, // orange-600 for pending (matches dedicated bg)
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

        // Approve Action if record is pending and is AdminPanel ($record is only set after an event is clicked)
        if ($this->isAdminPanel() && isset($this->record) && $this->record instanceof Schedule && $this->record->status === ScheduleStatus::Pending) {
            $approveAction = Action::make('approve')
                ->label('Finalize Room & Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->approveMatchingSchedule($this->record->id);
                })->authorize(fn () => Auth::check() && Auth::user()->can('Update:Schedule'));
            $actions[] = $approveAction;
        }

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

    public function approveMatchingSchedule(int $id): void
    {
        if (! Auth::check() || ! Auth::user()->can('Update:Schedule')) {
            return;
        }

        $schedule = Schedule::query()->where('id', $id)->first();
        if (! $schedule || $schedule->status !== ScheduleStatus::Pending) {
            Notification::make()
                ->title('Cannot approve')
                ->body('Schedule not found or not pending.')
                ->danger()
                ->send();

            return;
        }

        $targetRoomId = $this->getSelectedSlotRoomIdForSchema();
        if ($targetRoomId !== null && $schedule->room_id !== $targetRoomId) {
            $startCarbon = Carbon::parse($schedule->start_time);
            $endCarbon = Carbon::parse($schedule->end_time);
            // When moving a pending to the selected room, only Approved schedules block; other Pendings in that slot are competing requests we are resolving by approving this one.
            if (
                ScheduleOverlapChecker::hasOverlap(
                    $targetRoomId,
                    $startCarbon,
                    $endCarbon,
                    [ScheduleStatus::Approved],
                    excludeId: $schedule->id
                )
            ) {
                Notification::make()
                    ->title('Schedule conflict')
                    ->body('This room already has a schedule during the selected time.')
                    ->danger()
                    ->send();

                return;
            }
            $schedule->update(['room_id' => $targetRoomId]);
        }

        $finalRoomId = $schedule->room_id;
        $startCarbon = Carbon::parse($schedule->start_time);
        $endCarbon = Carbon::parse($schedule->end_time);
        if (
            ScheduleOverlapChecker::hasOverlap(
                $finalRoomId,
                $startCarbon,
                $endCarbon,
                [ScheduleStatus::Approved],
                excludeId: $schedule->id
            )
        ) {
            Notification::make()
                ->title('Schedule conflict')
                ->body('This room already has an approved schedule during this time.')
                ->danger()
                ->send();

            return;
        }

        $schedule->approve();

        $this->matchingPendingSchedules = collect($this->matchingPendingSchedules)
            ->filter(fn ($s) => (is_object($s) ? $s->id : ($s['id'] ?? null)) !== $id)
            ->values();

        Notification::make()
            ->title('Schedule approved')
            ->body('The pending request has been approved.')
            ->success()
            ->send();

        $this->refreshRecords();
        $this->unmountAction();
    }
}
