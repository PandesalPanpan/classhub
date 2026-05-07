<?php

namespace App\Livewire;

use App\Filament\Pages\Schemas\FindAvailableRoomsForm;
use App\Filament\Pages\Schemas\OverrideTemplateForm;
use App\Filament\Pages\Schemas\RequestScheduleForm;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\KeyEvent;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\Setting;
use App\ScheduleStatus;
use App\ScheduleType;
use App\Services\EmailNotificationService;
use App\Services\HandoverOperationalService;
use App\Services\ScheduleNotificationService;
use App\Services\ScheduleOverlapChecker;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
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
    protected static ?int $sort = 10;

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

    public ?string $findRoomsDate = null;

    public ?string $findRoomsStartTime = null;

    public ?int $findRoomsDurationMinutes = null;

    public ?int $prefillRoomId = null;

    public ?string $prefillStartTime = null;

    public ?string $prefillEndTime = null;

    public ?string $prefillRoomNumber = null;

    protected ?Collection $roomsCache = null;

    protected ?string $roomsCacheFilter = null;

    protected function headerActions(): array
    {
        $roomNumber = $this->filterRoom ? str_replace('room-', '', $this->filterRoom) : null;
        $label = $roomNumber ? "Room: {$roomNumber}" : 'Filter by Room';

        return [
            CreateAction::make()
                ->authorize(fn () => Auth::check() && Auth::user()->can('Create:Schedule'))
                ->extraModalFooterActions(fn () => $this->isAppPanel() ? [
                    Action::make('viewRules')
                        ->label('View Reservation & Policy Rules')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->modalHeading('Reservation and Policy Rules')
                        ->modalContent(view('filament.pages.reservation-rules'))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                ] : [])
                ->mountUsing(function ($form, array $arguments) {
                    $this->matchingPendingSchedules = collect();

                    // Initialize fillData with defaults
                    $fillData = [
                        'duration_minutes' => 60, // Default to 60 minutes
                    ];

                    // Pre-fill start_time and end_time when a date selection is made
                    if (isset($arguments['type']) && $arguments['type'] === 'select') {
                        $fillData['start_time'] = $arguments['start'] ?? null;
                        $fillData['end_time'] = $arguments['end'] ?? null;

                        // Clamp to app-panel time bounds (7:30 AM – 9:00 PM)
                        if ($this->isAppPanel() && isset($fillData['start_time'], $fillData['end_time'])) {
                            $start = Carbon::parse($fillData['start_time']);
                            $end = Carbon::parse($fillData['end_time']);
                            $earliest = $start->copy()->setTime(
                                \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_EARLIEST_HOUR,
                                \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_EARLIEST_MINUTE
                            );
                            $latest = $start->copy()->setTime(
                                \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_LATEST_HOUR,
                                \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_LATEST_MINUTE
                            );

                            if ($start->lt($earliest)) {
                                $start = $earliest->copy();
                            }
                            if ($end->gt($latest)) {
                                $end = $latest->copy();
                            }
                            if ($end->lte($start)) {
                                $end = $start->copy()->addMinutes(60);
                                if ($end->gt($latest)) {
                                    $end = $latest->copy();
                                }
                            }

                            $fillData['start_time'] = $start->format('Y-m-d H:i:s');
                            $fillData['end_time'] = $end->format('Y-m-d H:i:s');
                        }

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
                    } else {
                        // When clicking the header button directly (no calendar slot selected),
                        // still apply the room filter if set
                        if ($this->filterRoom) {
                            $roomNumber = str_replace('room-', '', $this->filterRoom);
                            $room = Room::where('room_number', $roomNumber)->first();
                            if ($room) {
                                $fillData['room_id'] = $room->id;
                            }
                        }
                    }

                    // Always fill the form with initialized data
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

                    $this->ensurePastScheduleAllowed($data);

                    if ($this->isAppPanel() && isset($data['start_time'], $data['end_time'])) {
                        $start = Carbon::parse($data['start_time']);
                        $end = Carbon::parse($data['end_time']);
                        $earliest = $start->copy()->setTime(
                            \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_EARLIEST_HOUR,
                            \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_EARLIEST_MINUTE
                        );
                        $latest = $start->copy()->setTime(
                            \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_LATEST_HOUR,
                            \App\Filament\Pages\Schemas\ScheduleFormOptions::APP_LATEST_MINUTE
                        );

                        if ($start->lt($earliest) || $end->gt($latest)) {
                            Notification::make()
                                ->title('Time out of range')
                                ->body('Schedules must start at or after 7:30 AM and end by 9:00 PM.')
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'start_time' => 'Schedules must start at or after 7:30 AM and end by 9:00 PM.',
                            ]);
                        }
                    }

                    // Overlap validation.
                    // App panel request flow should only block when an approved schedule exists,
                    // while admin flow keeps stricter overlap checks.
                    if (! empty($data['room_id']) && isset($data['start_time'], $data['end_time'])) {
                        $startCarbon = Carbon::parse($data['start_time']);
                        $endCarbon = Carbon::parse($data['end_time']);

                        $blockingStatuses = $this->isAppPanel()
                            ? [ScheduleStatus::Approved]
                            : [ScheduleStatus::Approved, ScheduleStatus::Pending];

                        $templateIdsToIgnore = $this->isAppPanel()
                            ? Schedule::query()
                                ->where('room_id', $data['room_id'])
                                ->where('type', ScheduleType::Template)
                                ->where('status', ScheduleStatus::Approved)
                                ->where('start_time', '<', $endCarbon)
                                ->where('end_time', '>', $startCarbon)
                                ->pluck('id')
                                ->all()
                            : [];

                        if (
                            ScheduleOverlapChecker::hasOverlap(
                                $data['room_id'],
                                $startCarbon,
                                $endCarbon,
                                $blockingStatuses,
                                excludeIds: $templateIdsToIgnore
                            )
                        ) {
                            $conflictMessage = $this->isAppPanel()
                                ? 'This room already has an approved schedule during the selected time.'
                                : 'This room already has a schedule during the selected time.';

                            Notification::make()
                                ->title('Schedule conflict')
                                ->body($conflictMessage)
                                ->danger()
                                ->send();

                            throw ValidationException::withMessages([
                                'start_time' => $conflictMessage,
                            ]);
                        }
                    }

                    return $data;
                })
                ->action(function (array $data, $livewire) {
                    $this->ensurePastScheduleAllowed($data);

                    $linkKeyEventId = $data['link_key_event_id'] ?? null;
                    unset($data['duration_minutes'], $data['link_key_event_id']);

                    // Admin panel schedules are auto-approved, app panel creates pending
                    if ($this->isAdminPanel()) {
                        $data['status'] = ScheduleStatus::Approved;
                        $data['approver_id'] = Auth::id();
                    } else {
                        $data['status'] = ScheduleStatus::Pending;
                    }

                    $schedule = Schedule::create($data);

                    if ($linkKeyEventId) {
                        KeyEvent::where('id', $linkKeyEventId)
                            ->whereNull('schedule_id')
                            ->update(['schedule_id' => $schedule->id]);
                    }

                    // Send notification if a pending schedule was created (app panel)
                    if (! $this->isAdminPanel() && $schedule->status === ScheduleStatus::Pending) {
                        ScheduleNotificationService::notifyPendingCreated($schedule);
                        EmailNotificationService::sendScheduleCreatedConfirmation($schedule);
                    }

                    // Refresh the calendar to show the newly created schedule
                    if ($livewire) {
                        $livewire->dispatch('filament-fullcalendar--refresh');
                    }
                }),
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->dispatch('filament-fullcalendar--refresh');
                }),
            Action::make('findAvailableRooms')
                ->label('Find rooms')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                ->visible(fn () => Auth::check())
                ->authorize(fn () => Auth::check())
                ->modalHeading('Find available rooms')
                ->modalSubmitActionLabel('Find rooms')
                ->modalWidth('xl')
                ->form(fn () => FindAvailableRoomsForm::schema($this))
                ->mountUsing(function (): void {
                    $this->findAvailableRoomsResults = [];
                    $this->findRoomsDate = null;
                    $this->findRoomsStartTime = null;
                    $this->findRoomsDurationMinutes = null;
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

                    $this->findRoomsDate = $date;
                    $this->findRoomsStartTime = $startTime;
                    $this->findRoomsDurationMinutes = $durationMinutes;

                    $blockingStatuses = $this->isAdminPanel()
                        ? [ScheduleStatus::Approved, ScheduleStatus::Pending]
                        : [ScheduleStatus::Approved];

                    $conflictingByRoom = Schedule::query()
                        ->whereIn('status', $blockingStatuses)
                        ->where('start_time', '<', $end->format('Y-m-d H:i:s'))
                        ->where('end_time', '>', $start->format('Y-m-d H:i:s'))
                        ->get(['id', 'room_id', 'type', 'subject', 'program_year_section', 'instructor', 'start_time', 'end_time'])
                        ->groupBy('room_id');

                    $rooms = Room::query()
                        ->where('is_active', true)
                        ->orderBy('room_number')
                        ->get();

                    $results = [];
                    foreach ($rooms as $room) {
                        $conflicts = $conflictingByRoom->get($room->id);
                        $hasRequestConflict = $conflicts?->contains(fn ($schedule) => $schedule->type !== ScheduleType::Template) ?? false;
                        $hasTemplateConflict = $conflicts?->contains(fn ($schedule) => $schedule->type === ScheduleType::Template) ?? false;
                        $conflicting = $conflicts?->first(fn ($schedule) => $schedule->type !== ScheduleType::Template) ?? $conflicts?->first();
                        $results[] = [
                            'room' => $room,
                            'available' => ! $hasRequestConflict,
                            'template_conflict' => ! $hasRequestConflict && $hasTemplateConflict,
                            'conflicting_schedule' => $conflicting,
                        ];
                    }

                    usort($results, function (array $a, array $b): int {
                        if ($a['available'] !== $b['available']) {
                            return $a['available'] ? -1 : 1;
                        }

                        if ($a['available'] && $b['available']) {
                            $aTemplate = $a['template_conflict'] ?? false;
                            $bTemplate = $b['template_conflict'] ?? false;
                            if ($aTemplate !== $bTemplate) {
                                return $aTemplate ? 1 : -1;
                            }
                        }

                        return strcmp(
                            $a['room']->room_number ?? '',
                            $b['room']->room_number ?? ''
                        );
                    });

                    $this->findAvailableRoomsResults = $results;

                    $idx = array_key_last($this->mountedActions);
                    if ($idx !== null && isset($this->cachedSchemas['mountedActionSchema'.$idx])) {
                        unset($this->cachedSchemas['mountedActionSchema'.$idx]);
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
        $schema = ScheduleForm::configure(Schema::make()->livewire($this), $matchingPendings, $selectedSlotRoomId);

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
            if (
                $roomId !== null && ScheduleOverlapChecker::hasOverlap(
                    (int) $roomId,
                    Carbon::parse($startTime),
                    Carbon::parse($endTime),
                    [ScheduleStatus::Approved]
                )
            ) {
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
        if (
            $selectedRoomId !== null && ScheduleOverlapChecker::hasOverlap(
                $selectedRoomId,
                Carbon::parse($startTime),
                Carbon::parse($endTime),
                [ScheduleStatus::Approved]
            )
        ) {
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
                'textColor' => '#ffffff',
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
            })
            ->mutateRecordDataUsing(function (array $data, Schedule $record): array {
                $data['start_time'] = $record->start_time->format('Y-m-d H:i:s');
                $data['end_time'] = $record->end_time->format('Y-m-d H:i:s');

                $durationMinutes = (int) $record->start_time->diffInMinutes($record->end_time);
                $isValidDuration = $durationMinutes >= 30 && $durationMinutes <= 810 && $durationMinutes % 30 === 0;
                if ($isValidDuration) {
                    $data['duration_minutes'] = $durationMinutes;
                }

                return $data;
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

        $activateAction = Action::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-bolt')
            ->color('success')
            ->visible(function (): bool {
                $record = $this->record;
                if (! $record instanceof Schedule || $record->type !== ScheduleType::Template) {
                    return false;
                }

                if (! $this->isAdminPanel()) {
                    return false;
                }

                if ($record->end_time->isPast()) {
                    return false;
                }

                return ! Schedule::query()
                    ->where('template_id', $record->id)
                    ->where('status', ScheduleStatus::Approved)
                    ->where('start_time', $record->start_time)
                    ->where('end_time', $record->end_time)
                    ->exists();
            })
            ->requiresConfirmation()
            ->modalHeading('Activate template')
            ->modalDescription(function (): string {
                $record = $this->record;
                if (! $record instanceof Schedule) {
                    return '';
                }

                $time = $record->start_time->format('g:i A').' – '.$record->end_time->format('g:i A');

                return "Create a tracked schedule from this template for today's class?\n\n"
                    ."{$record->subject} ({$record->program_year_section})\n"
                    .'Room: '.($record->room?->room_number ?? 'N/A')." · {$time}\n\n"
                    .'Key tracking and handover jobs will be dispatched immediately.';
            })
            ->modalSubmitActionLabel('Activate & Track Key')
            ->action(function (): void {
                $template = $this->record;
                if (! $template instanceof Schedule || $template->type !== ScheduleType::Template) {
                    return;
                }

                $alreadyActivated = Schedule::query()
                    ->where('template_id', $template->id)
                    ->where('status', ScheduleStatus::Approved)
                    ->where('start_time', $template->start_time)
                    ->where('end_time', $template->end_time)
                    ->exists();

                if ($alreadyActivated) {
                    Notification::make()
                        ->title('Already activated')
                        ->body('This template slot has already been activated.')
                        ->warning()
                        ->send();

                    return;
                }

                if (
                    ScheduleOverlapChecker::hasOverlap(
                        $template->room_id,
                        $template->start_time->copy(),
                        $template->end_time->copy(),
                        [ScheduleStatus::Approved],
                        excludeId: $template->id
                    )
                ) {
                    Notification::make()
                        ->title('Schedule conflict')
                        ->body('This room already has an approved schedule during this time.')
                        ->danger()
                        ->send();

                    return;
                }

                $activated = Schedule::create([
                    'room_id' => $template->room_id,
                    'requester_id' => Auth::id(),
                    'approver_id' => Auth::id(),
                    'template_id' => $template->id,
                    'subject' => $template->subject,
                    'program_year_section' => $template->program_year_section,
                    'instructor' => $template->instructor,
                    'type' => ScheduleType::Request,
                    'status' => ScheduleStatus::Approved,
                    'start_time' => $template->start_time,
                    'end_time' => $template->end_time,
                    'remarks' => $template->remarks,
                ]);

                Notification::make()
                    ->title('Template activated')
                    ->body("Schedule created and key tracking started for {$activated->subject}.")
                    ->success()
                    ->send();

                $this->unmountAction();
                $this->refreshRecords();
            })
            ->authorize(fn () => Auth::check() && Auth::user()->can('Create:Schedule'));

        $actions[] = $activateAction;

        $editAction = EditAction::make()
            ->authorize(function (Schedule $record) {
                return Auth::check() && Auth::user()->can('Update:Schedule');
            })
            ->hidden(function (Schedule $record) {
                return ! Auth::check() || ! Auth::user()->can('Update:Schedule');
            })
            ->mutateRecordDataUsing(function (array $data, Schedule $record): array {
                $data['start_time'] = $record->start_time->format('Y-m-d H:i:s');
                $data['end_time'] = $record->end_time->format('Y-m-d H:i:s');

                $durationMinutes = (int) $record->start_time->diffInMinutes($record->end_time);
                $isValidDuration = $durationMinutes >= 30 && $durationMinutes <= 810 && $durationMinutes % 30 === 0;
                if ($isValidDuration) {
                    $data['duration_minutes'] = $durationMinutes;
                }

                return $data;
            });
        $actions[] = $editAction;

        $deleteAction = DeleteAction::make()
            ->authorize(function (Schedule $record) {
                return Auth::check() && Auth::user()->can('Delete:Schedule');
            })
            ->hidden(function (Schedule $record) {
                return ! Auth::check() || ! Auth::user()->can('Delete:Schedule');
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

                $override = Schedule::create([
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

                ScheduleNotificationService::notifyOverrideRequested($override);

                // Send confirmation email to requester
                EmailNotificationService::sendScheduleOverridePendingConfirmation($override);

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

        $forceHandoverAction = Action::make('forceHandover')
            ->label('Force Handover (from previous)')
            ->icon('heroicon-o-hand-raised')
            ->color('info')
            ->visible(function (): bool {
                $record = $this->record;
                if (! $record instanceof Schedule) {
                    return false;
                }

                if (! $this->isAdminPanel()) {
                    return false;
                }

                if ($record->status !== ScheduleStatus::Approved) {
                    return false;
                }

                $hasAppliedHandoverAsNext = $record->handoverAsNext()
                    ->whereNotNull('resolution_finalized_at')
                    ->whereNotNull('previous_confirmed_at')
                    ->whereNotNull('next_confirmed_at')
                    ->exists();

                if ($hasAppliedHandoverAsNext) {
                    return false;
                }

                return Schedule::query()
                    ->where('room_id', $record->room_id)
                    ->where('id', '!=', $record->id)
                    ->where('status', ScheduleStatus::Approved)
                    ->where('end_time', '<=', $record->start_time)
                    ->where('end_time', '>=', $record->start_time->copy()->subMinutes(
                        (int) Setting::get('handover_eligibility_window_minutes')
                    ))
                    ->exists();
            })
            ->requiresConfirmation()
            ->modalHeading('Force Handover')
            ->modalDescription(function (): string {
                $record = $this->record;
                if (! $record instanceof Schedule) {
                    return '';
                }

                $previousSchedule = Schedule::query()
                    ->where('room_id', $record->room_id)
                    ->where('id', '!=', $record->id)
                    ->where('status', ScheduleStatus::Approved)
                    ->where('end_time', '<=', $record->start_time)
                    ->where('end_time', '>=', $record->start_time->copy()->subMinutes(
                        (int) Setting::get('handover_eligibility_window_minutes')
                    ))
                    ->orderByDesc('end_time')
                    ->first();

                if (! $previousSchedule) {
                    return 'No eligible previous schedule found.';
                }

                $prevTime = $previousSchedule->start_time->format('g:i A').' – '.$previousSchedule->end_time->format('g:i A');
                $nextTime = $record->start_time->format('g:i A').' – '.$record->end_time->format('g:i A');

                return "This will create and immediately apply a handover from the previous schedule to this one.\n\n"
                    ."Previous: {$previousSchedule->subject} ({$previousSchedule->program_year_section}) · {$prevTime}\n"
                    ."Next: {$record->subject} ({$record->program_year_section}) · {$nextTime}\n\n"
                    .'The key will be marked as HANDED_OVER and a synthetic USED event will be created for this schedule. '
                    .'Only use this when you have verified the physical key exchange occurred.';
            })
            ->modalSubmitActionLabel('Force Handover')
            ->action(function (): void {
                $record = $this->record;
                if (! $record instanceof Schedule) {
                    return;
                }

                $previousSchedule = Schedule::query()
                    ->where('room_id', $record->room_id)
                    ->where('id', '!=', $record->id)
                    ->where('status', ScheduleStatus::Approved)
                    ->where('end_time', '<=', $record->start_time)
                    ->where('end_time', '>=', $record->start_time->copy()->subMinutes(
                        (int) Setting::get('handover_eligibility_window_minutes')
                    ))
                    ->orderByDesc('end_time')
                    ->first();

                if (! $previousSchedule) {
                    Notification::make()
                        ->title('No eligible previous schedule')
                        ->body('Could not find a previous schedule in the handover window.')
                        ->warning()
                        ->send();

                    return;
                }

                $handover = ScheduleHandover::firstOrCreate(
                    ['previous_schedule_id' => $previousSchedule->id],
                    [
                        'next_schedule_id' => $record->id,
                        'resolution_deadline_at' => now(),
                    ]
                );

                $handover->update([
                    'next_schedule_id' => $record->id,
                    'resolution_finalized_at' => null,
                    'previous_confirmed_at' => now(),
                    'next_confirmed_at' => now(),
                    'forced_by' => Auth::id(),
                ]);

                HandoverOperationalService::apply($handover);

                Notification::make()
                    ->title('Handover applied')
                    ->body("Key handed over from {$previousSchedule->subject} to {$record->subject}. Key marked as HANDED_OVER.")
                    ->success()
                    ->send();

                $this->unmountAction();
                $this->refreshRecords();
            })
            ->authorize(fn () => Auth::check() && Auth::user()->can('Update:Schedule'));

        $actions[] = $forceHandoverAction;

        return $actions;
    }

    public function openCreateRequestFromFindRooms(int $roomId): void
    {
        if (! $this->isAppPanel() || ! Auth::check() || ! Auth::user()->can('Create:Schedule')) {
            return;
        }

        if (! $this->findRoomsDate || ! $this->findRoomsStartTime || ! $this->findRoomsDurationMinutes) {
            return;
        }

        $room = Room::query()->find($roomId);
        if (! $room) {
            return;
        }

        $start = Carbon::parse($this->findRoomsDate.' '.$this->findRoomsStartTime);
        $end = $start->copy()->addMinutes($this->findRoomsDurationMinutes);

        $this->prefillRoomId = $roomId;
        $this->prefillStartTime = $start->format('Y-m-d H:i:s');
        $this->prefillEndTime = $end->format('Y-m-d H:i:s');
        $this->prefillRoomNumber = $room->room_number;

        $this->unmountAction();

        $this->js(<<<'JS'
            setTimeout(() => {
                $wire.mountCreateFromFindRooms();
            }, 300);
        JS);
    }

    public function mountCreateFromFindRooms(): void
    {
        if (! $this->prefillRoomId || ! $this->prefillStartTime || ! $this->prefillEndTime || ! $this->prefillRoomNumber) {
            return;
        }

        $this->mountAction('create', [
            'type' => 'select',
            'start' => $this->prefillStartTime,
            'end' => $this->prefillEndTime,
            'resource' => [
                'id' => "room-{$this->prefillRoomNumber}",
            ],
        ]);

        $this->prefillRoomId = null;
        $this->prefillStartTime = null;
        $this->prefillEndTime = null;
        $this->prefillRoomNumber = null;
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
 
            $templateIdsInTarget = Schedule::query()
                ->where('room_id', $targetRoomId)
                ->where('type', ScheduleType::Template)
                ->where('status', ScheduleStatus::Approved)
                ->where('start_time', '<', $endCarbon)
                ->where('end_time', '>', $startCarbon)
                ->pluck('id')
                ->all();
            // When moving a pending to the selected room, only Approved schedules block; other Pendings in that slot are competing requests we are resolving by approving this one.
            if (
                ScheduleOverlapChecker::hasOverlap(
                    $targetRoomId,
                    $startCarbon,
                    $endCarbon,
                    [ScheduleStatus::Approved],
                    excludeId: $schedule->id,
                    excludeIds: $templateIdsInTarget
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
 
        $templateIdsToIgnore = Schedule::query()
            ->where('room_id', $finalRoomId)
            ->where('type', ScheduleType::Template)
            ->where('status', ScheduleStatus::Approved)
            ->where('start_time', '<', $endCarbon)
            ->where('end_time', '>', $startCarbon)
            ->pluck('id')
            ->all();
        if (
            ScheduleOverlapChecker::hasOverlap(
                $finalRoomId,
                $startCarbon,
                $endCarbon,
                [ScheduleStatus::Approved],
                excludeId: $schedule->id,
                excludeIds: $templateIdsToIgnore
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

    public function rejectMatchingSchedule(int $id): void
    {
        if (! Auth::check() || ! Auth::user()->can('Update:Schedule')) {
            return;
        }

        $schedule = Schedule::query()->where('id', $id)->first();
        if (! $schedule || $schedule->status !== ScheduleStatus::Pending) {
            Notification::make()
                ->title('Cannot reject')
                ->body('Schedule not found or not pending.')
                ->danger()
                ->send();

            return;
        }

        $schedule->reject();

        $this->matchingPendingSchedules = collect($this->matchingPendingSchedules)
            ->filter(fn ($s) => (is_object($s) ? $s->id : ($s['id'] ?? null)) !== $id)
            ->values();

        Notification::make()
            ->title('Schedule rejected')
            ->body('The pending request has been rejected.')
            ->success()
            ->send();

        $this->refreshRecords();
    }

    private function ensurePastScheduleAllowed(array $data): void
    {
        if ($this->isAdminPanel()) {
            return;
        }

        if ((bool) Setting::get('allow_past_schedule_requests')) {
            return;
        }

        if (empty($data['start_time'])) {
            return;
        }

        $startTime = Carbon::parse($data['start_time']);

        if ($startTime->isBefore(now())) {
            $formattedTime = $startTime->format('M j, Y g:i A');

            Notification::make()
                ->title('Past schedule requests are disabled')
                ->body("The selected time ({$formattedTime}) is in the past. Please choose a future time.")
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'start_time' => "The selected time ({$formattedTime}) is in the past. Please choose a future time.",
            ]);
        }
    }
}
