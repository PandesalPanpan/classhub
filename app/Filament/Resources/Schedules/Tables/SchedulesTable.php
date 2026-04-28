<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Filament\Resources\Schedules\Actions\SchedulesActions;
use App\Jobs\EndOfClassJob;
use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'handoverAsPrevious',
                'handoverAsNext',
            ]))
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
                ScheduleColumns::handoverStatus(),
                ScheduleColumns::scheduleTime(),
                ScheduleColumns::createdAt(),
                ScheduleColumns::updatedAt(),
            ])
            ->filters([
                ...ScheduleTableFilters::filters(includeRequester: true, defaultPendingStatus: true),
            ])
            ->recordActions([
                ...SchedulesActions::recordActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkReactivate')
                        ->label('Re-activate Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Re-activate Schedules')
                        ->modalDescription('This will set all selected EXPIRED schedules back to APPROVED and dispatch the appropriate key jobs. The verify job is skipped for schedules where its checkpoint has already passed (same logic as the single re-activate action).')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, $livewire) {
                            $reactivated = 0;
                            $records->each(function (Schedule $record) use (&$reactivated) {
                                if ($record->status !== ScheduleStatus::Expired) {
                                    return;
                                }

                                $record->update(['status' => ScheduleStatus::Approved]);

                                if ($record->end_time->isPast()) {
                                    EndOfClassJob::dispatch($record);
                                } elseif ($record->getFortyPercentDurationPoint()->isPast()) {
                                    EndOfClassJob::dispatch($record)->delay($record->end_time);
                                } else {
                                    $record->dispatchKeyJobs();
                                }
                                $reactivated++;
                            });

                            Notification::make()
                                ->title("{$reactivated} schedule(s) re-activated")
                                ->success()
                                ->send();

                            if ($livewire) {
                                $livewire->dispatch('filament-fullcalendar--refresh');
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
