<?php

namespace App\Filament\Pages;

use App\Models\Room;
use App\Models\ScheduleHandover;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ScheduleHandovers extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected string $view = 'filament.pages.schedule-handovers';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 86;

    protected static ?string $title = 'Schedule Handovers';

    protected static ?string $navigationLabel = 'Schedule Handovers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'previousSchedule.requester',
                'previousSchedule.room',
                'nextSchedule.requester',
                'nextSchedule.room',
                'forcedByUser',
            ]))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('previous_schedule_info')
                    ->label('From (Previous)')
                    ->getStateUsing(function (ScheduleHandover $record): ?string {
                        $schedule = $record->previousSchedule;
                        if (! $schedule) {
                            return null;
                        }

                        $time = $schedule->start_time->format('g:iA').'-'.$schedule->end_time->format('g:iA');
                        $room = $schedule->room?->room_number ?? '?';
                        $requester = $schedule->requester?->name ?? '?';

                        return "{$schedule->subject} ({$time})\n{$room} - {$requester}";
                    })
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('next_schedule_info')
                    ->label('To (Next)')
                    ->getStateUsing(function (ScheduleHandover $record): ?string {
                        $schedule = $record->nextSchedule;
                        if (! $schedule) {
                            return null;
                        }

                        $time = $schedule->start_time->format('g:iA').'-'.$schedule->end_time->format('g:iA');
                        $room = $schedule->room?->room_number ?? '?';
                        $requester = $schedule->requester?->name ?? '?';

                        return "{$schedule->subject} ({$time})\n{$room} - {$requester}";
                    })
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('handover_status')
                    ->label('Status')
                    ->getStateUsing(function (ScheduleHandover $record): string {
                        if ($record->wasForced()) {
                            return 'Forced';
                        }
                        if ($record->resolution_finalized_at && $record->isBothConfirmed()) {
                            return 'Confirmed';
                        }
                        if ($record->hasAnyDispute()) {
                            return 'Disputed';
                        }
                        if ($record->resolution_finalized_at) {
                            return 'Finalized';
                        }

                        return 'Pending';
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Confirmed' => 'success',
                        'Forced' => 'info',
                        'Disputed' => 'danger',
                        'Pending' => 'warning',
                        'Finalized' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('previous_confirmed_at')
                    ->label('Prev Confirmed')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('next_confirmed_at')
                    ->label('Next Confirmed')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('previous_disputed_at')
                    ->label('Prev Disputed')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('next_disputed_at')
                    ->label('Next Disputed')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('forcedByUser.name')
                    ->label('Forced By')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('resolution_finalized_at')
                    ->label('Finalized')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('resolution_deadline_at')
                    ->label('Deadline')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M j, g:i:s A') : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M j, Y g:i:s A'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'disputed' => 'Disputed',
                        'forced' => 'Forced',
                        'finalized' => 'Finalized',
                    ])
                    ->query(function (Builder $query, array $data): void {
                        match ($data['value'] ?? null) {
                            'pending' => $query->whereNull('resolution_finalized_at')
                                ->where(function (Builder $q) {
                                    $q->whereNull('previous_confirmed_at')
                                        ->orWhereNull('next_confirmed_at');
                                })
                                ->whereNull('previous_disputed_at')
                                ->whereNull('next_disputed_at')
                                ->whereNull('forced_by'),
                            'confirmed' => $query->whereNotNull('resolution_finalized_at')
                                ->whereNotNull('previous_confirmed_at')
                                ->whereNotNull('next_confirmed_at')
                                ->whereNull('forced_by'),
                            'disputed' => $query->where(function (Builder $q) {
                                $q->whereNotNull('previous_disputed_at')
                                    ->orWhereNotNull('next_disputed_at');
                            }),
                            'forced' => $query->whereNotNull('forced_by'),
                            'finalized' => $query->whereNotNull('resolution_finalized_at'),
                            default => null,
                        };
                    }),
                SelectFilter::make('room')
                    ->label('Room')
                    ->options(fn () => Room::query()->orderBy('room_number')->pluck('room_number', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $roomId) => $q->where(function (Builder $sub) use ($roomId) {
                            $sub->whereHas('previousSchedule', fn (Builder $sq) => $sq->where('room_id', $roomId))
                                ->orWhereHas('nextSchedule', fn (Builder $sq) => $sq->where('room_id', $roomId));
                        })
                    ))
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_dispute')
                    ->label('Has Dispute')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function (Builder $q) {
                            $q->whereNotNull('previous_disputed_at')
                                ->orWhereNotNull('next_disputed_at');
                        }),
                        false: fn (Builder $query) => $query->whereNull('previous_disputed_at')
                            ->whereNull('next_disputed_at'),
                    ),
            ]);
    }

    public function getTableQuery(): Builder
    {
        return ScheduleHandover::query();
    }
}
