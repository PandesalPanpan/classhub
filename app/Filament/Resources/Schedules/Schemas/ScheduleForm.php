<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\ScheduleStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ScheduleForm
{
    /**
     * @param  array<int, \App\Models\Schedule>|Collection<int, \App\Models\Schedule>  $matchingPendingSchedules
     */
    public static function configure(Schema $schema, array|Collection $matchingPendingSchedules = []): Schema
    {
        $matchingPendingSchedules = collect($matchingPendingSchedules);

        $components = [];

        if ($matchingPendingSchedules->isNotEmpty()) {
            $components[] = Section::make('Matching Pending Requests')
                ->description('Pending schedule requests that match this time slot. Approve one to fill the slot without creating a new schedule.')
                ->schema([
                    ViewField::make('matching_pendings')
                        ->view('filament.components.matching-pending-schedules')
                        ->viewData(['schedules' => $matchingPendingSchedules])
                        ->dehydrated(false),
                ]);
        }

        return $schema
            ->components(array_merge($components, [
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
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(30)
                            ->native(false)
                            ->displayFormat('F j Y g:iA')
                            ->format('Y-m-d H:i:s'),
                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(30)
                            ->native(false)
                            ->displayFormat('F j Y g:iA')
                            ->format('Y-m-d H:i:s'),
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
}
