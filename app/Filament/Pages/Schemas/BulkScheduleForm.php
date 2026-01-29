<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Room;
use App\ScheduleStatus;
use App\Services\ScheduleOverlapChecker;
use App\ScheduleType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

class BulkScheduleForm
{
    /**
     * Determine if the current schedule type is a template.
     *
     * Template schedules are "soft" patterns, so we force their status
     * to Approved and hide other status options.
     */
    protected static function isTemplateType(?string $type): bool
    {
        return $type === ScheduleType::Template->value;
    }

    public static function schema(): array
    {
        return [
            Section::make('Room & Subject')
                ->description('Select the room and subject for the schedule.')
                ->schema([
                    Select::make('room_id')
                        ->label('Room')
                        ->options(fn() => Room::query()
                            ->where('is_active', true)
                            ->orderBy('room_number')
                            ->get()
                            ->mapWithKeys(fn($room) => [$room->id => $room->room_full_label ?? $room->room_number])
                            ->toArray())
                        ->searchable()
                        ->preload(true)
                        ->required()
                        ->live(),

                    TextInput::make('subject')
                        ->label('Subject / Purpose')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Methods of Research / Lab Activity')
                        ->live(),

                    TextInput::make('program_year_section')
                        ->label('Program Year & Section')
                        ->maxLength(255)
                        ->placeholder('e.g. BSCPE 4-3P')
                        ->live(),

                    TextInput::make('instructor')
                        ->label('Instructor')
                        ->placeholder('e.g. Rolito Mahaguay')
                        ->live(),
                ])
                ->columns(2),
            Section::make('Schedule Details')
                ->description('Set up the weekly schedule pattern. Select which days of the week this schedule applies.')
                ->schema([
                    Select::make('days_of_week')
                        ->label('Days of Week')
                        ->columnSpanFull()
                        ->options([
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            0 => 'Sunday',
                        ])
                        ->multiple()
                        ->required()
                        ->helperText('Select one or more days when this schedule occurs (e.g., Monday and Thursday)')
                        ->placeholder('Select days...')
                        ->searchable()
                        ->live(),

                    Fieldset::make('Time & Duration')
                        ->schema([
                            TimePicker::make('start_time')
                                ->label('Start Time')
                                ->required()
                                ->seconds(false)
                                ->minutesStep(30)
                                ->native(false)
                                ->displayFormat('g:i A')
                                ->format('H:i:s')
                                ->helperText('The start time for each occurrence')
                                ->live(),

                            Select::make('duration_minutes')
                                ->label('Duration')
                                ->options([
                                    30 => '30 minutes',
                                    60 => '1 hour',
                                    90 => '1.5 hours',
                                    120 => '2 hours',
                                    150 => '2.5 hours',
                                    180 => '3 hours',
                                ])
                                ->default(60)
                                ->required()
                                ->helperText('How long each class session lasts')
                                ->live(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Fieldset::make('Semester Range')
                        ->schema([
                            DatePicker::make('semester_start_date')
                                ->label('Semester Start Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('F j Y')
                                ->format('Y-m-d')
                                ->default(now()->format('Y-m-d'))
                                ->helperText('When should the schedule generation begin?')
                                ->live(),

                            DatePicker::make('semester_end_date')
                                ->label('Semester End Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('F j Y')
                                ->format('Y-m-d')
                                ->minDate(fn(Get $get) => $get('semester_start_date') ? Carbon::parse($get('semester_start_date'))->format('Y-m-d') : null)
                                ->helperText('When should the schedule generation end? (e.g., end of semester)')
                                ->live(onBlur: true),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Additional Settings')
                ->schema([
                    Select::make('type')
                        ->default(ScheduleType::Template->value)
                        ->options([
                            ScheduleType::Template->value => 'Template',
                            ScheduleType::Request->value => 'Request',
                        ])
                        ->live()
                        ->required(),

                    Select::make('status')
                        ->label('Status')
                        ->options(fn(Get $get) => self::isTemplateType($get('type'))
                            ? [
                                ScheduleStatus::Approved->value => 'Approved',
                            ]
                            : ScheduleStatus::class)
                        ->default(ScheduleStatus::Approved->value)
                        ->disabled(fn(Get $get) => self::isTemplateType($get('type')))
                        ->required()
                        ->helperText('Initial status for all created schedules'),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Optional remarks to add to all schedules'),
                ]),

            Section::make('Preview')
                ->description('Preview of schedules that will be created based on your settings.')
                ->schema([
                    TextEntry::make('schedule_preview')
                        ->label('')
                        ->state(fn(Get $get) => self::generatePreview($get)),
                ])
                ->visible(fn(Get $get) => self::canShowPreview($get)),
        ];
    }

    protected static function canShowPreview(Get $get): bool
    {
        $daysOfWeek = $get('days_of_week');
        $startDate = $get('semester_start_date');
        $endDate = $get('semester_end_date');
        $startTime = $get('start_time');
        $duration = $get('duration_minutes');

        return !empty($daysOfWeek) && $startDate && $endDate && $startTime && $duration;
    }

    protected static function generatePreview(Get $get): HtmlString
    {
        $data = [
            'room_id' => $get('room_id'),
            'subject' => $get('subject'),
            'program_year_section' => $get('program_year_section'),
            'days_of_week' => $get('days_of_week') ?? [],
            'semester_start_date' => $get('semester_start_date'),
            'semester_end_date' => $get('semester_end_date'),
            'start_time' => $get('start_time'),
            'duration_minutes' => $get('duration_minutes') ?? 60,
        ];

        $preview = self::calculatePreview($data);

        // Get room name
        $roomName = null;
        if ($data['room_id']) {
            $room = Room::find($data['room_id']);
            $roomName = $room ? ($room->room_full_label ?? $room->room_number) : null;
        }

        // Check for conflicts if room is selected
        if ($data['room_id'] && !empty($preview['schedules'])) {
            $preview = self::checkConflicts($preview, $data['room_id']);
        }

        return new HtmlString(
            View::make('filament.components.bulk-schedule-preview', [
                'data' => $data,
                'preview' => $preview,
                'roomName' => $roomName,
            ])->render()
        );
    }

    protected static function calculatePreview(array $data): array
    {
        $schedules = [];
        $duration = (int) ($data['duration_minutes'] ?? 60);

        $daysOfWeek = collect($data['days_of_week'] ?? [])
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if (empty($daysOfWeek) || empty($data['semester_start_date']) || empty($data['semester_end_date']) || empty($data['start_time'])) {
            return ['total' => 0, 'schedules' => []];
        }

        $startDate = Carbon::parse($data['semester_start_date'])->startOfDay();
        $endDate = Carbon::parse($data['semester_end_date'])->endOfDay();

        if ($startDate->gt($endDate)) {
            return ['total' => 0, 'schedules' => []];
        }

        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $currentDate = $startDate->copy();

        // Collect all schedules
        while ($currentDate->lte($endDate)) {
            if (in_array($currentDate->dayOfWeek, $daysOfWeek, true)) {
                $startDateTime = Carbon::parse($currentDate->toDateString() . ' ' . ($data['start_time'] ?? '00:00:00'));
                $endDateTime = $startDateTime->copy()->addMinutes($duration);

                $schedules[] = [
                    'date' => $currentDate->format('F j, Y'),
                    'day' => $dayNames[$currentDate->dayOfWeek] ?? '',
                    'time' => $startDateTime->format('g:i A') . ' - ' . $endDateTime->format('g:i A'),
                    'start_time' => $startDateTime,
                    'end_time' => $endDateTime,
                    'has_conflict' => false,
                ];
            }

            $currentDate->addDay();
        }

        return [
            'total' => count($schedules),
            'schedules' => $schedules,
        ];
    }

    protected static function checkConflicts(array $preview, int $roomId): array
    {
        if (empty($preview['schedules'])) {
            return $preview;
        }

        // Prepare time ranges for batch checking
        $timeRanges = collect($preview['schedules'])
            ->map(fn($schedule) => [
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
            ])
            ->all();

        // Use the service to check for conflicts
        $conflicts = ScheduleOverlapChecker::checkBatchConflicts($roomId, $timeRanges);

        // Mark conflicts in preview schedules
        foreach ($preview['schedules'] as $index => $schedule) {
            $rangeKey = $schedule['start_time']->toIso8601String() . '-' . $schedule['end_time']->toIso8601String();

            if (isset($conflicts[$rangeKey])) {
                $existingSchedule = $conflicts[$rangeKey];
                $preview['schedules'][$index]['has_conflict'] = true;

                // Build conflict description with subject, section, and instructor
                $conflictParts = [];
                if ($existingSchedule->subject) {
                    $conflictParts[] = $existingSchedule->subject;
                }
                if ($existingSchedule->program_year_section) {
                    $conflictParts[] = '(' . $existingSchedule->program_year_section . ')';
                }
                if ($existingSchedule->instructor) {
                    $conflictParts[] = '- ' . $existingSchedule->instructor;
                }

                $preview['schedules'][$index]['conflict_with'] = !empty($conflictParts)
                    ? implode(' ', $conflictParts)
                    : 'Existing Schedule';
            }
        }

        return $preview;
    }
}
