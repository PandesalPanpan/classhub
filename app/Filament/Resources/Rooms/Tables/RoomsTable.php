<?php

namespace App\Filament\Resources\Rooms\Tables;

use App\KeyStatus;
use App\Models\Room;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultPaginationPageOption(25)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['key']))
            ->columns([
                TextColumn::make('room_number')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('room_type')
                    ->formatStateUsing(fn ($state) => Str::title(strtolower($state->value)))
                    ->searchable(),
                TextColumn::make('capacity')
                    ->searchable(),
                // TextColumn::make('schedules_count')
                //     ->label('Schedules')
                //     ->getStateUsing(fn($record) => $record->schedules->count())
                //     ->sortable(),
                TextColumn::make('key.status')
                    ->label('Key')
                    // Keep state as status (enum/string) for color matching
                    ->getStateUsing(fn ($record) => $record->key?->status)
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->key) {
                            return 'No key assigned';
                        }

                        $statusLabel = $state instanceof KeyStatus ? $state->value : (string) $state;

                        $statusLabel = match ($statusLabel) {
                            KeyStatus::Used->value => 'IN USE',
                            KeyStatus::Stored->value => 'STORED',
                            KeyStatus::Disabled->value => 'DISABLED',
                            KeyStatus::HandedOver->value => 'HANDOVER',
                            KeyStatus::Missing->value => 'MISSING',
                            default => $statusLabel,
                        };

                        return "{$record->key->slot_number} • {$statusLabel}";
                    })
                    ->badge()
                    ->color(function (KeyStatus|string|null $state) {
                        if (! $state) {
                            return 'secondary';
                        }

                        $status = $state instanceof KeyStatus ? $state : KeyStatus::from($state);

                        return match ($status) {
                            KeyStatus::Used => 'danger',
                            KeyStatus::Stored => 'success',
                            KeyStatus::Disabled => 'secondary',
                            KeyStatus::HandedOver => 'info',
                            KeyStatus::Missing => 'danger',
                        };
                    })
                    ->searchable(),
                TextColumn::make('current_schedule')
                    ->label('Current Schedule')
                    ->getStateUsing(function (Room $record): ?string {
                        $schedule = $record->getCurrentKeySchedule();

                        if (! $schedule) {
                            return null;
                        }

                        return trim(($schedule->subject ?? '-').' - '.($schedule->program_year_section ?? '-'));
                    })
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('current_representative')
                    ->label('Representative')
                    ->getStateUsing(fn (Room $record): ?string => $record->getCurrentKeySchedule()?->requester?->name)
                    ->placeholder('—')
                    ->toggleable(),
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
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleKeyTracking')
                    ->label(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled ? 'Enable Key' : 'Disable Key')
                    ->icon(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled ? 'Enable Key Tracking?' : 'Disable Key Tracking?')
                    ->modalDescription(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled
                        ? 'Key tracking will resume. The system will monitor this key and send alerts normally.'
                        : 'Key tracking will be paused. The system will NOT send missing key alerts for this key until re-enabled. Use this when you know a class is staying for a consecutive slot.')
                    ->modalSubmitActionLabel(fn (Room $record): string => $record->key?->status === KeyStatus::Disabled ? 'Enable' : 'Disable')
                    ->visible(fn (Room $record): bool => $record->key !== null)
                    ->action(function (Room $record): void {
                        if (! $record->key) {
                            return;
                        }
                        $newStatus = $record->key->status === KeyStatus::Disabled ? KeyStatus::Stored : KeyStatus::Disabled;
                        $record->key->update(['status' => $newStatus]);
                        Notification::make()
                            ->title($newStatus === KeyStatus::Disabled ? 'Key tracking disabled' : 'Key tracking re-enabled')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
