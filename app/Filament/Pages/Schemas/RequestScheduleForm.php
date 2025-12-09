<?php

namespace App\Filament\Pages\Schemas;

use App\ScheduleStatus;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RequestScheduleForm
{
    public static function schema(): array
    {
        return [
            Select::make('room_id')
                ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                    return $query->where('is_active', true);
                })
                ->required(),
            TextInput::make('title')
                ->required(),

            Section::make('Schedule')
                ->schema([
                    DateTimePicker::make('start_time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('F j Y g:iA')
                        ->format('Y-m-d H:i:s')
                        ->live(onBlur: true)
                        ->columnSpan(1)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateEndTime($get, $set);
                        }),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->native(false)
                        ->displayFormat('F j Y g:iA')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

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
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateEndTime($get, $set);
                        }),
                ])
                ->columns(2),
        ];
    }

    protected static function updateEndTime(Get $get, Set $set): void
    {
        $start = $get('start_time');
        $duration = $get('duration_minutes');

        if (! $start || ! $duration) {
            $set('end_time', null);
            return;
        }

        $set('end_time', Carbon::parse($start)->addMinutes($duration));
    }
}
