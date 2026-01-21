<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Room;
use App\ScheduleStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class BulkScheduleForm
{
    public static function schema(): array
    {
        return [
            Select::make('room_id')
                ->label('Room')
                ->options(fn () => Room::query()
                    ->where('is_active', true)
                    ->orderBy('room_number')
                    ->get()
                    ->mapWithKeys(fn ($room) => [$room->id => $room->room_full_label ?? $room->room_number])
                    ->toArray())
                ->searchable()
                ->preload(true)
                ->required(),
            
            TextInput::make('subject')
                ->label('Subject / Purpose')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Methods of Research / Lab Activity'),

            TextInput::make('program_year_section')
                ->label('Program Year & Section')
                ->maxLength(255)
                ->placeholder('e.g. BSCPE 4-3P'),

            Section::make('Schedule Details')
                ->description('Set up the weekly schedule pattern. Select which days of the week this schedule applies.')
                ->schema([
                    Select::make('days_of_week')
                        ->label('Days of Week')
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
                        ->searchable(),

                    TimePicker::make('start_time')
                        ->label('Start Time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('g:i A')
                        ->format('H:i:s')
                        ->helperText('The start time for each occurrence'),

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
                        ->helperText('How long each class session lasts'),

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
                        ->minDate(fn (Get $get) => $get('semester_start_date') ? Carbon::parse($get('semester_start_date'))->format('Y-m-d') : null)
                        ->helperText('When should the schedule generation end? (e.g., end of semester)')
                        ->live(onBlur: true),
                ])
                ->columns(2),

            Section::make('Additional Settings')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(ScheduleStatus::class)
                        ->default(ScheduleStatus::Approved->value)
                        ->required()
                        ->helperText('Initial status for all created schedules'),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Optional remarks to add to all schedules'),
                ]),
        ];
    }
}


