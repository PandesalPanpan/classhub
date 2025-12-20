<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Filament\Resources\Schedules\Actions\SchedulesActions;
use App\Filament\Resources\Schedules\Tables\ScheduleColumns;
use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('room.room_number')
                    ->label('Room#')
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->searchable(),
                TextColumn::make('approver.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('program_year_section')
                    ->label('PYS')
                    ->tooltip('Program Year & Section')
                    ->searchable(),
                TextColumn::make('instructorInitials')
                    ->label('Instructor')
                    ->searchable(),
                ScheduleColumns::status(),
                TextColumn::make('schedule_time')
                    ->label('Schedule')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('start_time', $direction);
                    })
                    ->getStateUsing(function (Schedule $record): string {
                        if (! $record->start_time || ! $record->end_time) {
                            return 'N/A';
                        }

                        $start = Carbon::parse($record->start_time);
                        $end = Carbon::parse($record->end_time);

                        // If same day, show date once: "Dec 19, 2025 7:30AM-9:30AM"
                        if ($start->isSameDay($end)) {
                            return $start->format('M j, Y') . ' ' . $start->format('g:iA') . '-' . $end->format('g:iA');
                        }

                        // If different days, show both dates
                        return $start->format('M j, Y g:iA') . ' - ' . $end->format('M j, Y g:iA');
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ScheduleStatus::class)
                    ->default(ScheduleStatus::Pending),
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
                ...SchedulesActions::recordActions(),
            ])
            ->toolbarActions([
                    // BulkAction::make('approve')
                    //     ->label('Approve')
                    //     ->icon('heroicon-o-check-circle')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Approve Schedule')
                    //     ->modalSubmitActionLabel('Approve')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->modalWidth('md')
                    //     ->action(function (Collection $records, $livewire) {
                    //         $records->each(function (Schedule $record) {
                    //             $record->approve();
                    //         });
                    //             if ($livewire) {
                    //             $livewire->dispatch('filament-fullcalendar--refresh');
                    //         }
                    //     }),
                    // BulkAction::make('reject')
                    //     ->label('Reject')
                    //     ->icon('heroicon-o-x-circle')
                    //     ->color('danger')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Reject Schedule')
                    //     ->modalSubmitActionLabel('Reject')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->modalWidth('md')
                    //     ->action(function (Collection $records, $livewire) {
                    //         $records->each(function (Schedule $record) {
                    //             $record->reject();
                    //         });
                    //             if ($livewire) {
                    //             $livewire->dispatch('filament-fullcalendar--refresh');
                    //         }
                    //     })
                // BulkActionGroup::make([
                    
                //     // DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
