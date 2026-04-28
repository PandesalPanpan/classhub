<?php

namespace App\Filament\Resources\Schedules\Actions;

use App\Jobs\EndOfClassJob;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\Services\HandoverOperationalService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

class SchedulesActions
{
    public static function recordActions(): array
    {
        return [
            self::approveAction(),
            self::rejectAction(),
            ActionGroup::make([
                self::reactivateAction(),
                self::forceApplyHandoverAction(),
                self::finalizeHandoverAction(),
            ])
                ->label('Recovery')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('gray')
                ->dropdown()
                ->visible(fn (Schedule $record) => in_array($record->status, [
                    ScheduleStatus::Expired,
                    ScheduleStatus::Approved,
                ], true)),
        ];
    }

    private static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('Approve Schedule')
            ->modalSubmitActionLabel('Finalize Room & Approve')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Request Details')
                    ->description('Review the schedule request details.')
                    ->schema([
                        Select::make('room_id')
                            ->label('Final Room Assignment')
                            ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                                return $query->where('is_active', true);
                            })
                            ->required()
                            ->helperText('Select the final room assignment for this schedule'),
                        TextInput::make('subject')
                            ->label('Subject / Purpose')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('program_year_section')
                            ->label('Program Year & Section')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('instructor')
                            ->label('Instructor')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('requester_id')
                            ->label('Requester')
                            ->relationship('requester', 'name')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Schedule')
                    ->description('Review the scheduled time and duration.')
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false)
                            ->displayFormat('M j, Y g:iA')
                            ->columnSpan(1),
                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false)
                            ->displayFormat('M j, Y g:iA')
                            ->columnSpan(1),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Textarea::make('remarks')
                    ->label('Remarks')
                    ->columnSpanFull(),
            ])
            ->fillForm(fn (Schedule $record): array => [
                'room_id' => $record->room_id,
                'subject' => $record->subject,
                'program_year_section' => $record->program_year_section,
                'instructor' => $record->instructor,
                'requester_id' => $record->requester_id,
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
                'remarks' => $record->remarks,
            ])
            ->visible(fn (Schedule $record) => $record->status === ScheduleStatus::Pending)
            ->action(function (Schedule $record, array $data, $livewire) {
                $record->update([
                    'room_id' => $data['room_id'],
                    'remarks' => $data['remarks'],
                ]);

                $record->approve();

                $record->refresh();

                if ($livewire) {
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
            });
    }

    private static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Reject Schedule')
            ->modalSubmitActionLabel('Reject Schedule')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth('2xl')
            ->schema([
                Section::make('Request Details')
                    ->description('Review the schedule request details.')
                    ->schema([
                        Select::make('room_id')
                            ->label('Room Assignment')
                            ->relationship('room', 'room_number', modifyQueryUsing: function (Builder $query) {
                                return $query->where('is_active', true);
                            })
                            ->helperText('Optional – room assignment if applicable'),
                        TextInput::make('subject')
                            ->label('Subject / Purpose')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('program_year_section')
                            ->label('Program Year & Section')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('instructor')
                            ->label('Instructor')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('requester_id')
                            ->label('Requester')
                            ->relationship('requester', 'name')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Schedule')
                    ->description('Review the scheduled time and duration.')
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false)
                            ->displayFormat('M j, Y g:iA')
                            ->columnSpan(1),
                        DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false)
                            ->displayFormat('M j, Y g:iA')
                            ->columnSpan(1),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Textarea::make('remarks')
                    ->label('Rejection Reason')
                    ->required()
                    ->helperText('Please provide a reason for rejecting this schedule request.')
                    ->columnSpanFull(),
            ])
            ->fillForm(fn (Schedule $record): array => [
                'room_id' => $record->room_id,
                'subject' => $record->subject,
                'program_year_section' => $record->program_year_section,
                'instructor' => $record->instructor,
                'requester_id' => $record->requester_id,
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
                'remarks' => $record->remarks,
            ])
            ->visible(fn (Schedule $record) => $record->status === ScheduleStatus::Pending)
            ->action(function (Schedule $record, array $data, $livewire) {
                $record->update([
                    'remarks' => $data['remarks'],
                ]);

                if (isset($data['room_id'])) {
                    $record->room_id = $data['room_id'];
                }

                $record->reject();

                if ($livewire) {
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
            });
    }

    private static function reactivateAction(): Action
    {
        return Action::make('reactivate')
            ->label('Re-activate')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Re-activate Expired Schedule')
            ->modalDescription('This will set the schedule back to APPROVED and dispatch the appropriate key jobs. Use this when a schedule was wrongly expired (e.g., IoT scanner missed the key event, or key was MISSING from a previous class). The verify job is skipped if its check point has already passed.')
            ->modalSubmitActionLabel('Re-activate')
            ->visible(fn (Schedule $record) => $record->status === ScheduleStatus::Expired)
            ->action(function (Schedule $record, $livewire) {
                $record->update([
                    'status' => ScheduleStatus::Approved,
                ]);

                if ($record->end_time->isPast()) {
                    EndOfClassJob::dispatch($record);
                } elseif ($record->getFortyPercentDurationPoint()->isPast()) {
                    EndOfClassJob::dispatch($record)->delay($record->end_time);
                } else {
                    $record->dispatchKeyJobs();
                }

                Notification::make()
                    ->title('Schedule re-activated')
                    ->body('Status set to APPROVED. Key jobs dispatched.')
                    ->success()
                    ->send();

                if ($livewire) {
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
            });
    }

    private static function forceApplyHandoverAction(): Action
    {
        return Action::make('forceApplyHandover')
            ->label('Force Apply Handover')
            ->icon('heroicon-o-hand-raised')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Force Apply Handover')
            ->modalDescription('This will force-confirm the handover from this schedule to the next schedule, create a synthetic USED event for the next class, and mark the key as HANDED_OVER. Only use this when you have physically verified the key exchange.')
            ->modalSubmitActionLabel('Force Apply')
            ->visible(function (Schedule $record) {
                return $record->handoverAsPrevious()
                    ->whereNull('resolution_finalized_at')
                    ->exists();
            })
            ->action(function (Schedule $record) {
                $handover = $record->handoverAsPrevious()
                    ->whereNull('resolution_finalized_at')
                    ->first();

                if (! $handover) {
                    Notification::make()
                        ->title('No pending handover found')
                        ->warning()
                        ->send();

                    return;
                }

                $handover->update([
                    'previous_confirmed_at' => now(),
                    'next_confirmed_at' => now(),
                ]);

                HandoverOperationalService::apply($handover);

                Notification::make()
                    ->title('Handover applied successfully')
                    ->body('Key marked as HANDED_OVER. Synthetic USED event created for the next schedule.')
                    ->success()
                    ->send();
            });
    }

    private static function finalizeHandoverAction(): Action
    {
        return Action::make('finalizeHandover')
            ->label('Finalize Handover')
            ->icon('heroicon-o-check-badge')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Finalize Pending Handover')
            ->modalDescription('This will close the pending handover without applying it. Use this when the handover situation has been resolved manually (key returned, handover cancelled, etc.).')
            ->modalSubmitActionLabel('Finalize')
            ->visible(function (Schedule $record) {
                return $record->handoverAsPrevious()
                    ->whereNull('resolution_finalized_at')
                    ->exists();
            })
            ->action(function (Schedule $record) {
                $handover = $record->handoverAsPrevious()
                    ->whereNull('resolution_finalized_at')
                    ->first();

                if ($handover) {
                    $handover->markFinalized();

                    Notification::make()
                        ->title('Handover finalized')
                        ->body('The pending handover has been closed without applying it.')
                        ->success()
                        ->send();
                }
            });
    }
}
