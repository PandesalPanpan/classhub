<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ActiveSchedulesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Currently Active Schedules')
            ->poll('30s')
            ->query($this->getTableQuery())
            ->defaultSort('end_time')
            ->columns([
                TextColumn::make('room.room_number')
                    ->label('Room')
                    ->placeholder('-'),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->label('Requester')
                    ->placeholder('-'),
                TextColumn::make('start_time')
                    ->dateTime('M j, Y g:i A'),
                TextColumn::make('end_time')
                    ->dateTime('M j, Y g:i A'),
                TextColumn::make('time_remaining')
                    ->label('Time Remaining')
                    ->state(fn (Schedule $record): string => now()->diffForHumans($record->end_time, true)),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return Schedule::query()
            ->with(['room', 'requester'])
            ->where('status', ScheduleStatus::Approved)
            ->where('type', ScheduleType::Request)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }
}
