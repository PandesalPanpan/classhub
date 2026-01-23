<?php

namespace App\Filament\Resources\Schedules\Actions;

use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\Room;
use Filament\Actions\Action;
use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SchedulesActions
{
    public static function recordActions(): array
    {
        return [
            Action::make('approve')
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
                ->fillForm(fn(Schedule $record): array => [
                    'room_id' => $record->room_id,
                    'subject' => $record->subject,
                    'program_year_section' => $record->program_year_section,
                    'instructor' => $record->instructor,
                    'requester_id' => $record->requester_id,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time,
                    'remarks' => $record->remarks,
                ])
                ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending)
                ->action(function (Schedule $record, array $data, $livewire) {
                    $record->update([
                        'room_id' => $data['room_id'],
                        'status' => ScheduleStatus::Approved,
                        'approver_id' => Auth::id(),
                        'remarks' => $data['remarks'],
                    ]);

                    // Refresh to get updated relationships
                    $record->refresh();

                    // Dispatch job only if the room key is not disabled
                    $room = Room::with('key')->find($data['room_id']);
                    if ($room?->key?->status !== KeyStatus::Disabled) {
                        VerifyScheduleKeyUsageJob::dispatch($record)
                            ->delay($record->getFortyPercentDurationPoint());
                    }

                    if ($livewire) {
                        $livewire->dispatch('filament-fullcalendar--refresh');
                    }
                })
                ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending),
            Action::make('reject')
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
                ->fillForm(fn(Schedule $record): array => [
                    'room_id' => $record->room_id,
                    'subject' => $record->subject,
                    'program_year_section' => $record->program_year_section,
                    'instructor' => $record->instructor,
                    'requester_id' => $record->requester_id,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time,
                    'remarks' => $record->remarks,
                ])
                ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending)
                ->action(function (Schedule $record, array $data, $livewire) {
                    $updateData = [
                        'status' => ScheduleStatus::Rejected,
                        'approver_id' => Auth::id(),
                        'remarks' => $data['remarks'],
                    ];

                    if (isset($data['room_id'])) {
                        $updateData['room_id'] = $data['room_id'];
                    }

                    $record->update($updateData);

                    if ($livewire) {
                        $livewire->dispatch('filament-fullcalendar--refresh');
                    }
                }),
        ];
    }
}
