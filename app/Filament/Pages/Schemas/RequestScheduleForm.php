<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RequestScheduleForm
{
    public static function schema(): array
    {
        return [
            Section::make('Request Details')
                ->description('Tell us what this request is for.')
                ->schema([
                    Select::make('room_id')
                        ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                            return $query->where('is_active', true);
                        })
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->room_full_label)
                        ->label('Preferred Room')
                        ->placeholder('Optional – choose your preferred room')
                        ->helperText('Admins assign final room.'),
                    TextInput::make('subject')
                        ->label('Subject / Purpose')
                        ->placeholder('e.g. Methods of Research / Lab Activity')
                        ->required(),
                    TextInput::make('program_year_section')
                        ->label('Program Year & Section')
                        ->placeholder('e.g. BSCPE 4-3P')
                        ->required(),
                    TextInput::make('instructor')
                        ->label('Instructor')
                        ->placeholder('e.g. Rolito Mahaguay'),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
            Section::make('Schedule')
                ->description('Choose when you need the room. End time is calculated automatically from the duration.')
                ->schema([
                    Hidden::make('start_time'),

                    DatePicker::make('start_date')
                        ->label('Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('F j, Y')
                        ->minDate(fn () => Setting::get('allow_past_schedule_requests') ? null : now())
                        ->helperText(fn () => Setting::get('allow_past_schedule_requests')
                            ? 'You can request schedules in the past and future.'
                            : 'Past schedule requests are currently disabled by the administrator.')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            $startTime = $get('start_time');
                            if ($startTime) {
                                $set('start_date', Carbon::parse($startTime)->format('Y-m-d'));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateStartTime($get, $set);
                            static::updateEndTime($get, $set);
                        }),

                    Select::make('start_time_slot')
                        ->label('Time')
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
                            static::updateStartTime($get, $set);
                            static::updateEndTime($get, $set);
                        }),

                    Select::make('duration_minutes')
                        ->label('Duration')
                        ->options(ScheduleFormOptions::durationMinutesOptions())
                        ->default(60)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateEndTime($get, $set);
                        }),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->native(false)
                        ->displayFormat('F j Y g:i A')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
        ];
    }

    protected static function updateStartTime(Get $get, Set $set): void
    {
        $date = $get('start_date');
        $timeSlot = $get('start_time_slot');

        if (! $date || ! $timeSlot) {
            $set('start_time', null);

            return;
        }

        $set('start_time', Carbon::parse($date)->setTimeFromTimeString($timeSlot.':00')->format('Y-m-d H:i:s'));
    }

    protected static function updateEndTime(Get $get, Set $set): void
    {
        $start = $get('start_time');
        $duration = $get('duration_minutes');

        if (! $start || $duration === null || $duration === '') {
            $set('end_time', null);

            return;
        }

        $end = Carbon::parse($start)
            ->addMinutes((int) $duration)
            ->format('Y-m-d H:i:s');

        $set('end_time', $end);
    }
}
