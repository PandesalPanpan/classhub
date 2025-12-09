<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\ScheduleStatus;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('room_id')
                    ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                        return $query->where('is_active', true);
                    })
                    ->prefix("Room: ")
                    ->required(),
                Select::make('requester_id')
                    ->relationship('requester', 'name')
                    ->required(),
                Select::make('approver_id')
                    ->relationship('approver', 'name'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('block'),
                Select::make('status')
                    ->options(ScheduleStatus::class)
                    ->default('PENDING')
                    ->required(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->required(),
                Textarea::make('remarks')
                    ->columnSpanFull(),
            ]);
    }
}
