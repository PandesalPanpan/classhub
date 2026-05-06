<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\KeyStatus;
use App\Models\KeyEvent;
use App\ScheduleStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing(['keyEvents', 'requester', 'room.key']);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('createUsedEvent')
                ->label('Record Key Pickup')
                ->icon('heroicon-o-key')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Record Manual Key Pickup')
                ->modalDescription('Create a manual USED key event for this schedule. Use this when the IoT scanner missed the key pickup but the key was physically taken.')
                ->modalSubmitActionLabel('Record Pickup')
                ->schema([
                    DateTimePicker::make('occurred_at')
                        ->label('Picked Up At')
                        ->default(fn () => $this->record->start_time)
                        ->required()
                        ->native(false)
                        ->displayFormat('M j, Y g:iA')
                        ->helperText('When was the key physically picked up?'),
                ])
                ->visible(fn (): bool => $this->record->status === ScheduleStatus::Approved
                    && $this->record->room?->key !== null)
                ->action(function (array $data): void {
                    $key = $this->record->room->key;

                    KeyEvent::firstOrCreate(
                        [
                            'key_id' => $key->id,
                            'schedule_id' => $this->record->id,
                            'status' => KeyStatus::Used->value,
                            'source' => 'manual',
                        ],
                        [
                            'occurred_at' => $data['occurred_at'],
                        ]
                    );

                    if ($key->status !== KeyStatus::Used) {
                        $key->update(['status' => KeyStatus::Used]);
                    }

                    Notification::make()
                        ->title('Key pickup recorded')
                        ->body('A manual USED event has been created for this schedule.')
                        ->success()
                        ->send();
                }),
            Action::make('activities')
                ->label('Activity Log')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn (): string => ScheduleResource::getUrl('activities', ['record' => $this->record])),
        ];
    }
}
