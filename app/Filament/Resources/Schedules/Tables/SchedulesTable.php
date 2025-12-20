<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Filament\Resources\Schedules\Actions\SchedulesActions;
use App\Filament\Resources\Schedules\Tables\ScheduleColumns;
use App\ScheduleStatus;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
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
