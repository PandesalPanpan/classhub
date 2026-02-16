<?php

namespace App\Filament\Pages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;

class FindAvailableRoomsForm
{
    /**
     * Schema for the Find Available Rooms modal. The widget is passed so the
     * ViewField can display the current findAvailableRoomsResults.
     *
     * @param  object  $widget  The Livewire component (CalendarWidget) with findAvailableRoomsResults
     * @return array<int, mixed>
     */
    public static function schema(object $widget): array
    {
        return [
            Section::make('Time slot')
                ->description('Enter the date, start time, and duration to check room availability.')
                ->schema([
                    DatePicker::make('date')
                        ->label('Date')
                        ->required()
                        ->live()
                        ->native(false)
                        ->displayFormat('F j Y')
                        ->format('Y-m-d'),
                    TimePicker::make('start_time')
                        ->label('Start time')
                        ->required()
                        ->live()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('g:i A')
                        ->format('H:i:s'),
                    Select::make('duration_minutes')
                        ->label('Duration')
                        ->options(RequestScheduleForm::durationMinutesOptions())
                        ->default(60)
                        ->required(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 3,
                ]),
            Section::make('Results')
                ->description('Rooms available first, then those with conflicts.')
                ->schema([
                    ViewField::make('find_available_rooms_results')
                        ->view('filament.components.find-available-rooms-results')
                        ->viewData(['results' => $widget->findAvailableRoomsResults ?? []])
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
