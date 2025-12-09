<?php

namespace App\Filament\Resources\Rooms\Tables;

use App\KeyStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('room_number')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('key.status')
                    ->label('Key')
                    // Keep state as status (enum/string) for color matching
                    ->getStateUsing(fn($record) => $record->key?->status)
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->key) {
                            return 'No key assigned';
                        }

                        $statusLabel = $state instanceof KeyStatus ? $state->value : (string) $state;

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
                            KeyStatus::Stored => 'warning',
                            KeyStatus::Disabled => 'secondary',
                        };
                    })
                    ->searchable(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
