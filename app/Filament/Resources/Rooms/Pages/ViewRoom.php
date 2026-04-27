<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use App\KeyStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('viewActivities')
                ->label('Activity Log')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn (): string => RoomResource::getUrl('activities', ['record' => $this->record])),
            Action::make('toggleKeyTracking')
                ->label(fn (): string => $this->record->key?->status === KeyStatus::Disabled ? 'Enable Key Tracking' : 'Disable Key Tracking')
                ->icon(fn (): string => $this->record->key?->status === KeyStatus::Disabled ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                ->color(fn (): string => $this->record->key?->status === KeyStatus::Disabled ? 'success' : 'warning')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->key?->status === KeyStatus::Disabled ? 'Enable Key Tracking?' : 'Disable Key Tracking?')
                ->modalDescription(fn (): string => $this->record->key?->status === KeyStatus::Disabled
                    ? 'Key tracking will resume. The system will monitor this key and send alerts normally.'
                    : 'Key tracking will be paused. The system will NOT send missing key alerts for this key until re-enabled.')
                ->modalSubmitActionLabel(fn (): string => $this->record->key?->status === KeyStatus::Disabled ? 'Enable' : 'Disable')
                ->visible(fn (): bool => $this->record->key !== null)
                ->action(function (): void {
                    $this->record->refresh();
                    if (! $this->record->key) {
                        return;
                    }
                    $newStatus = $this->record->key->status === KeyStatus::Disabled ? KeyStatus::Stored : KeyStatus::Disabled;
                    $this->record->key->update(['status' => $newStatus]);
                    Notification::make()
                        ->title($newStatus === KeyStatus::Disabled ? 'Key tracking disabled' : 'Key tracking re-enabled')
                        ->success()
                        ->send();
                }),
        ];
    }
}
