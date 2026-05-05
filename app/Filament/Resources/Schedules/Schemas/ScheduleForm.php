<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Filament\Pages\Schemas\ScheduleFormOptions;
use App\ScheduleStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleForm
{
    /**
     * @param  array<int, \App\Models\Schedule>|Collection<int, \App\Models\Schedule>  $matchingPendingSchedules
     */
    public static function configure(Schema $schema, array|Collection $matchingPendingSchedules = [], ?int $selectedSlotRoomId = null): Schema
    {
        $matchingPendingSchedules = collect($matchingPendingSchedules);

        $components = [];

        if ($matchingPendingSchedules->isNotEmpty()) {
            $components[] = Section::make('Matching Pending Requests')
                ->description('Pending schedule requests that match this time slot. Approve one to fill the slot without creating a new schedule. Includes requests for this time in other rooms; approving one from another room will assign it to the selected room.')
                ->schema([
                    ViewField::make('matching_pendings')
                        ->view('filament.components.matching-pending-schedules')
                        ->viewData([
                            'schedules' => $matchingPendingSchedules,
                            'selectedSlotRoomId' => $selectedSlotRoomId,
                        ])
                        ->dehydrated(false),
                ]);
        }

        return $schema->components(array_merge($components, [
            Section::make('Schedule Details')
                ->description('Basic information about this schedule.')
                ->schema([
                    Select::make('room_id')
                        ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                            return $query->where('is_active', true);
                        })
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->room_full_label)
                        ->label('Room')
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options(ScheduleStatus::class)
                        ->default('PENDING')
                        ->required(),
                    TextInput::make('subject')
                        ->label('Subject / Purpose')
                        ->placeholder('e.g. Methods of Research / Lab Activity')
                        ->required(),
                    TextInput::make('program_year_section')
                        ->label('Program Year & Section')
                        ->placeholder('e.g. BSCPE 4-3P'),
                    TextInput::make('instructor')
                        ->label('Instructor')
                        ->placeholder('e.g. Rolito Mahaguay'),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
            Section::make('People')
                ->description('Who requested and approved this schedule.')
                ->schema([
                    Select::make('requester_id')
                        ->relationship('requester', 'name')
                        ->label('Requester')
                        ->searchable()
                        ->preload(),
                    Select::make('approver_id')
                        ->relationship('approver', 'name')
                        ->label('Approver')
                        ->searchable()
                        ->preload(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
            Section::make('Time & Date')
                ->description('When this schedule takes place.')
                ->schema([
                    Hidden::make('start_time'),
                    Hidden::make('end_time')
                        ->rules([
                            fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                $startTime = $get('start_time');
                                if ($value && $startTime && Carbon::parse($value)->lte(Carbon::parse($startTime))) {
                                    $fail('End time must be after start time.');
                                }
                            },
                        ]),

                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('F j, Y')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            $startTime = $get('start_time');
                            if ($startTime) {
                                $set('start_date', Carbon::parse($startTime)->format('Y-m-d'));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateCombinedTime('start', $get, $set);
                        }),

                    Select::make('start_time_slot')
                        ->label('Start Time')
                        ->options(ScheduleFormOptions::timeSlotOptions())
                        ->required()
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            $startTime = $get('start_time');
                            if ($startTime) {
                                $set('start_time_slot', Carbon::parse($startTime)->format('H:i'));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateCombinedTime('start', $get, $set);
                        }),

                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('F j, Y')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            $endTime = $get('end_time');
                            if ($endTime) {
                                $set('end_date', Carbon::parse($endTime)->format('Y-m-d'));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateCombinedTime('end', $get, $set);
                        }),

                    Select::make('end_time_slot')
                        ->label('End Time')
                        ->options(ScheduleFormOptions::timeSlotOptions())
                        ->required()
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            $endTime = $get('end_time');
                            if ($endTime) {
                                $set('end_time_slot', Carbon::parse($endTime)->format('H:i'));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateCombinedTime('end', $get, $set);
                        }),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
            Section::make('Additional Information')
                ->description('Any additional notes or remarks.')
                ->schema([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder('Add any additional notes or information...')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]));
    }

    protected static function updateCombinedTime(string $prefix, Get $get, Set $set): void
    {
        $date = $get("{$prefix}_date");
        $timeSlot = $get("{$prefix}_time_slot");

        if (! $date || ! $timeSlot) {
            $set("{$prefix}_time", null);

            return;
        }

        $set("{$prefix}_time", Carbon::parse($date)->setTimeFromTimeString($timeSlot.':00')->format('Y-m-d H:i:s'));
    }
}
