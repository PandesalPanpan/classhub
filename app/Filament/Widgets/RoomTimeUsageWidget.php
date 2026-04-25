<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\ScheduleStatus;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RoomTimeUsageWidget extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Room Usage Duration')
            ->query(
                Schedule::query()
                    ->join('rooms', 'rooms.id', '=', 'schedules.room_id')
                    ->where('schedules.status', ScheduleStatus::Approved)
                    ->selectRaw('MIN(schedules.id) as id')
                    ->selectRaw('schedules.room_id')
                    ->selectRaw('rooms.room_number')
                    ->selectRaw('COUNT(*) as sessions_count')
                    ->selectRaw('SUM(EXTRACT(EPOCH FROM (schedules.end_time - schedules.start_time)) / 3600) as total_hours')
                    ->groupBy('schedules.room_id', 'rooms.room_number')
            )
            ->defaultSort('total_hours', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('room_number')
                    ->label('Room')
                    ->searchable(),
                TextColumn::make('sessions_count')
                    ->label('Sessions')
                    ->numeric(),
                TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->state(fn (Schedule $record): string => number_format((float) $record->total_hours, 2)),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Date Range')
                    ->schema([
                        DateTimePicker::make('from')
                            ->label('From')
                            ->seconds(false),
                        DateTimePicker::make('to')
                            ->label('To')
                            ->seconds(false),
                    ])
                    ->query(function (Builder $query, array $data): void {
                        $from = $data['from'] ?? null;
                        $to = $data['to'] ?? null;

                        if (filled($from)) {
                            $query->where('schedules.end_time', '>=', Carbon::parse($from)->format('Y-m-d H:i:s'));
                        }

                        if (filled($to)) {
                            $query->where('schedules.start_time', '<=', Carbon::parse($to)->format('Y-m-d H:i:s'));
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = Indicator::make('From '.Carbon::parse($data['from'])->format('M j, Y g:i A'))
                                ->removeField('from');
                        }

                        if (filled($data['to'] ?? null)) {
                            $indicators[] = Indicator::make('To '.Carbon::parse($data['to'])->format('M j, Y g:i A'))
                                ->removeField('to');
                        }

                        return $indicators;
                    }),
            ]);
    }
}
