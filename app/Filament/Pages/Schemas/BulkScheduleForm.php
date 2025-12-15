<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Room;
use App\ScheduleStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                ->required()
                ->options(fn () => Room::query()
                    ->where('is_active', true)
                    ->orderBy('room_number')
                    ->pluck('room_number', 'id'))
                ->searchable(),
            
            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255),

            TextInput::make('block')
                ->label('Block')
                ->maxLength(255),

            Section::make('Schedule Details')
                ->schema([
                    DateTimePicker::make('start_time')
                        ->label('First Occurrence Start Time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('F j Y g:i A')
                        ->format('Y-m-d H:i:s')
                        ->helperText('The start time for the first occurrence'),
                    
                    DateTimePicker::make('end_time')
                        ->label('First Occurrence End Time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('F j Y g:i A')
                        ->format('Y-m-d H:i:s')
                        ->helperText('The end time for each occurrence'),
                ])
                ->columns(2),

            Section::make('Recurrence Pattern')
                ->schema([
                    Select::make('recurrence_type')
                        ->label('Repeat')
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->default('weekly')
                        ->required()
                        ->live()
                        ->helperText('How often should this schedule repeat?'),

                    Select::make('recurrence_end_type')
                        ->label('Ends')
                        ->options([
                            'after' => 'After a number of occurrences',
                            'on' => 'On a specific date',
                        ])
                        ->default('after')
                        ->required()
                        ->live()
                        ->helperText('When should the recurrence stop?'),

                    Select::make('occurrences')
                        ->label('Number of Occurrences')
                        ->options(array_combine(range(1, 52), range(1, 52)))
                        ->default(12)
                        ->required()
                        ->visible(fn (Get $get) => $get('recurrence_end_type') === 'after')
                        ->helperText('Total number of occurrences to create'),

                    DateTimePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->native(false)
                        ->displayFormat('F j Y')
                        ->format('Y-m-d')
                        ->minDate(fn (Get $get) => $get('start_time') ? Carbon::parse($get('start_time'))->format('Y-m-d') : null)
                        ->visible(fn (Get $get) => $get('recurrence_end_type') === 'on')
                        ->helperText('Last date to create occurrences'),
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


