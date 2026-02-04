<?php

namespace App\Filament\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                    DateTimePicker::make('start_time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(30)
                        ->native(false)
                        ->displayFormat('F j Y g:iA')
                        ->format('Y-m-d H:i:s')
                        ->live()
                        ->columnSpan(1)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateEndTime($get, $set);
                        })
                        ->afterStateUpdatedJs(<<<'JS'
                            const start = $state;
                            const duration = parseInt($get('duration_minutes') || 0, 10);
                            if (!start || !duration) {
                                $set('end_time', null);
                                return;
                            }
                            const d = new Date(start.replace(' ', 'T'));
                            d.setMinutes(d.getMinutes() + duration);
                            const pad = n => String(n).padStart(2, '0');
                            $set('end_time', d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00');
                        JS),

                    DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->native(false)
                        ->displayFormat('F j Y g:iA')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpan(1),

                    Select::make('duration_minutes')
                        ->label('Duration')
                        ->options(static::durationMinutesOptions())
                        ->default(60)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            static::updateEndTime($get, $set);
                        })
                        ->afterStateUpdatedJs(<<<'JS'
                            const start = $get('start_time');
                            const duration = parseInt($state || 0, 10);
                            if (!start || !duration) {
                                $set('end_time', null);
                                return;
                            }
                            const d = new Date(start.replace(' ', 'T'));
                            d.setMinutes(d.getMinutes() + duration);
                            const pad = n => String(n).padStart(2, '0');
                            $set('end_time', d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00');
                        JS),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),
        ];
    }

    /**
     * Duration options from 30 minutes up to max (7:30am–9pm = 13.5 hours).
     * Steps of 30 minutes for consistency with minutesStep on the date picker.
     *
     * @return array<int, string>
     */
    public static function durationMinutesOptions(): array
    {
        $options = [];
        foreach (range(30, 810, 30) as $minutes) {
            $options[$minutes] = match (true) {
                $minutes < 60 => "{$minutes} minutes",
                $minutes % 60 === 0 => (int) ($minutes / 60).' '.str('hour')->plural((int) ($minutes / 60)),
                default => (int) ($minutes / 60).'.5 hours',
            };
        }

        return $options;
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
