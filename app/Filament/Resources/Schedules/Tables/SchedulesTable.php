<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room.room_number')
                    ->label('Room#')
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->searchable(),
                TextColumn::make('approver.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('block')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function (ScheduleStatus|string|null $state): string {
                        if (! $state) {
                            return 'Unknown';
                        }

                        $value = $state instanceof ScheduleStatus ? $state->value : $state;

                        return Str::title(strtolower($value)); // e.g. "PENDING" -> "Pending"
                    })
                    ->color(function (ScheduleStatus|string|null $state): string {
                        if (! $state) {
                            return 'secondary';
                        }

                        $status = $state instanceof ScheduleStatus ? $state : ScheduleStatus::from($state);

                        return match ($status) {
                            ScheduleStatus::Pending => 'warning',
                            ScheduleStatus::Approved => 'success',
                            ScheduleStatus::Rejected => 'danger',
                            ScheduleStatus::Cancelled => 'secondary',
                            ScheduleStatus::Completed => 'success',
                            ScheduleStatus::Expired => 'secondary',
                        };
                    })
                    ->searchable(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending)
                    ->action(function (Schedule $record) {
                        $record->approve();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending)
                    ->action(function (Schedule $record) {
                        $record->reject();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function (Schedule $record) {
                                $record->approve();
                            });
                        }),
                    BulkAction::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function (Schedule $record) {
                                $record->reject();
                            });
                        }),
                // BulkActionGroup::make([
                    
                //     // DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
