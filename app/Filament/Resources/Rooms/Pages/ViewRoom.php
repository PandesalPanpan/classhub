<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use App\KeyStatus;
use App\Models\KeyEvent;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
            Action::make('viewKeyActivities')
                ->label('Key Activity Log')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->visible(fn (): bool => $this->record->key !== null)
                ->modalHeading('Key Activity Log')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn () => view('filament.resources.rooms.pages.partials.key-activity-log', [
                    'room' => $this->record->load(['key.events.schedule']),
                ])),
            Action::make('markKeyReturned')
                ->label('Mark Key Returned')
                ->icon('heroicon-o-arrow-uturn-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark Key as Returned')
                ->modalDescription('This will set the key status to STORED and create a STORED key event. Use this when the key has been physically returned but the IoT scanner didn\'t register it.')
                ->modalSubmitActionLabel('Mark Returned')
                ->schema([
                    DateTimePicker::make('returned_at')
                        ->label('Returned At')
                        ->default(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('M j, Y g:iA')
                        ->helperText('When was the key physically returned? Defaults to now.'),
                ])
                ->visible(fn (): bool => $this->record->key !== null && in_array($this->record->key->status, [
                    KeyStatus::Missing,
                    KeyStatus::Used,
                    KeyStatus::HandedOver,
                ], true))
                ->action(function (array $data): void {
                    $this->record->refresh();
                    $key = $this->record->key;

                    if (! $key) {
                        return;
                    }

                    KeyEvent::create([
                        'key_id' => $key->id,
                        'schedule_id' => $this->record->getCurrentKeySchedule()?->id,
                        'status' => KeyStatus::Stored->value,
                        'source' => 'manual',
                        'occurred_at' => $data['returned_at'],
                    ]);

                    $key->update(['status' => KeyStatus::Stored]);

                    Notification::make()
                        ->title('Key marked as returned')
                        ->body('Key status set to STORED. A manual STORED event has been recorded.')
                        ->success()
                        ->send();
                }),
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
