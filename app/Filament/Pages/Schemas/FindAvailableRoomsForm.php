<?php

namespace App\Filament\Pages\Schemas;

use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

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
                        ->format('Y-m-d')
                        ->minDate(fn () => Setting::get('allow_past_schedule_requests') ? null : today()),
                    Select::make('start_time')
                        ->label('Start time')
                        ->options(fn (): array => Filament::getCurrentPanel()?->getId() === 'app'
                            ? ScheduleFormOptions::appTimeSlotOptions()
                            : ScheduleFormOptions::timeSlotOptions())
                        ->required()
                        ->searchable()
                        ->live(),
                    Select::make('duration_minutes')
                        ->label('Duration')
                        ->options(function (Get $get): array {
                            $options = Filament::getCurrentPanel()?->getId() === 'app'
                                ? ScheduleFormOptions::appDurationOptions($get('start_time'))
                                : ScheduleFormOptions::durationMinutesOptions();

                            $current = $get('duration_minutes');

                            if ($current !== null && $current !== '' && ! array_key_exists((int) $current, $options)) {
                                $currentInt = (int) $current;
                                $label = match (true) {
                                    $currentInt < 60 => "{$currentInt} minutes",
                                    $currentInt % 60 === 0 => (int) ($currentInt / 60).' '.str('hour')->plural((int) ($currentInt / 60)),
                                    default => (int) ($currentInt / 60).'.5 hours',
                                };

                                $options[$currentInt] = $label;
                                ksort($options);
                            }

                            return $options;
                        })
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
                        ->viewData([
                            'results' => $widget->findAvailableRoomsResults ?? [],
                            'panelId' => Filament::getCurrentPanel()?->getId(),
                        ])
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
