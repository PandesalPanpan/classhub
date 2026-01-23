<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\RequestScheduleForm;
use App\Filament\Resources\Schedules\Tables\ScheduleColumns;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\Services\ScheduleOverlapChecker;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RequestSchedule extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.request-schedule';

    public function getDescription(): string
    {
        return 'Request a schedule for a classroom';
    }

    protected static ?string $title = 'My Schedule Requests';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_time', 'desc')
            ->columns([
                ScheduleColumns::roomNumber(withFallback: true),
                ScheduleColumns::requesterName()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->options(ScheduleStatus::class)
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Request')
                    ->modalDescription('Are you sure you want to cancel this request? This action cannot be undone.')
                    ->modalSubmitActionLabel('Cancel Request')
                    ->modalCancelActionLabel('Keep Request')
                    ->modalWidth('md')
                    ->visible(fn (Schedule $record) => $record->status === ScheduleStatus::Pending)
                    ->action(function (Schedule $record, $livewire) {
                        $record->cancel();

                        if ($livewire) {
                            $livewire->dispatch('filament-fullcalendar--refresh');
                        }
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create Request')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(Auth::user()->can('Create:Schedule'))
                    ->schema(RequestScheduleForm::schema())
                    ->extraModalFooterActions([
                        Action::make('viewRules')
                            ->label('View Reservation & Policy Rules')
                            ->icon('heroicon-o-document-text')
                            ->color('gray')
                            ->modalHeading('Reservation and Policy Rules')
                            ->modalContent(view('filament.pages.reservation-rules'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close'),
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['requester_id'] = Auth::id();

                        if (isset($data['start_time']) && isset($data['duration_minutes'])) {
                            $start = \Carbon\Carbon::parse($data['start_time']);
                            $data['end_time'] = $start->copy()->addMinutes($data['duration_minutes'])->format('Y-m-d H:i:s');
                        }

                        return $data;
                    })
                    ->action(function (array $data, $livewire) {
                        unset($data['duration_minutes']);

                        // Server-side overlap validation (Pending + Approved in same room)
                        // Only check for conflicts when a room has been selected.
                        if (! empty($data['room_id'])) {
                            if (ScheduleOverlapChecker::hasOverlap(
                                $data['room_id'],
                                Carbon::parse($data['start_time']),
                                Carbon::parse($data['end_time'])
                            )) {
                                Notification::make()
                                    ->title('Schedule conflict')
                                    ->body('This room already has a schedule during the selected time.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'start_time' => 'This room already has a schedule during the selected time.',
                                ]);
                            }
                        }

                        Schedule::create($data);

                        if ($livewire) {
                            $livewire->dispatch('filament-fullcalendar--refresh');
                        }
                    }),
            ]);
    }

    public function getTableQuery(): Builder
    {
        return Schedule::query()->where('requester_id', Auth::id());
    }
}
