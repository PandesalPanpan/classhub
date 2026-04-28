<?php

namespace App\Filament\Widgets;

use App\Models\QueueJob;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveJobsQueueWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Queued Jobs')
            ->poll('10s')
            ->query(QueueJob::query()->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('queue')
                    ->sortable(),
                TextColumn::make('job_class')
                    ->label('Job Class')
                    ->searchable(),
                TextColumn::make('attempts')
                    ->sortable(),
                TextColumn::make('reserved_at')
                    ->label('Reserved At')
                    ->state(fn (QueueJob $record): string => $record->reserved_at ? Carbon::createFromTimestamp((int) $record->reserved_at, config('app.timezone'))->format('M j, Y g:i A') : '-'),
                TextColumn::make('available_at')
                    ->label('Available At')
                    ->state(fn (QueueJob $record): string => Carbon::createFromTimestamp((int) $record->available_at, config('app.timezone'))->format('M j, Y g:i A')),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->state(fn (QueueJob $record): string => Carbon::createFromTimestamp((int) $record->created_at, config('app.timezone'))->format('M j, Y g:i A')),
            ]);
    }
}
