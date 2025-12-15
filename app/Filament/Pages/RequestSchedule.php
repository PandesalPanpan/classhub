<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\RequestScheduleForm;
use App\Models\Schedule;
use App\ScheduleStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable(),
                TextColumn::make('start_time')
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
                    ->visible(fn(Schedule $record) => $record->status === ScheduleStatus::Pending)
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
                    ->schema(RequestScheduleForm::schema())
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
                        if ($this->hasOverlap(
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

    /**
     * Check for overlapping schedules in the same room.
     */
    protected function hasOverlap(int $roomId, Carbon $start, Carbon $end): bool
    {
        return Schedule::query()
            ->where('room_id', $roomId)
            ->whereIn('status', [ScheduleStatus::Approved, ScheduleStatus::Pending])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();
    }
}
