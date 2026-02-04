<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Filament\Resources\Schedules\Actions\SchedulesActions;
use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->searchUsing(function (Builder $query, string $search): void {
                [$parsedDate, $textSearch, $dateCandidate] = Schedule::extractDateAndTextFromSearch($search);
                Schedule::applyTableSearchConstraint($query, $textSearch);
                if ($parsedDate !== null) {
                    $query->where(function (Builder $q) use ($parsedDate, $dateCandidate): void {
                        Schedule::applyScheduleOverlapConstraint($q, $parsedDate, $dateCandidate);
                    });
                }
            })
            ->columns([
                ScheduleColumns::roomNumber(),
                ScheduleColumns::requesterName(),
                ScheduleColumns::approverName(),
                ScheduleColumns::subject(),
                ScheduleColumns::programYearSection(),
                ScheduleColumns::instructorInitials(),
                ScheduleColumns::status(),
                ScheduleColumns::scheduleTime(),
                ScheduleColumns::createdAt(),
                ScheduleColumns::updatedAt(),
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
