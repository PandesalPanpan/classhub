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
use App\Services\ScheduleOverlapChecker;

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
                TextColumn::make('room.room_number')
                    ->label('Room#')
                    ->getStateUsing(fn($record) => $record->room?->room_number ?? 'N/A')
                    ->searchable(),
                // TextColumn::make('requester.name')
                //     ->searchable(),
                TextColumn::make('approver.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('program_year_section')
                    ->label('PYS')
                    ->tooltip('Program Year & Section')
                    ->searchable(),
                TextColumn::make('instructor')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable()
                    ->badge(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA')),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA'))
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
