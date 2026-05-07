<?php

namespace App\Filament\Pages;

use App\KeyStatus;
use App\Models\KeyEvent;
use App\Models\Room;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class KeyEvents extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected string $view = 'filament.pages.key-events';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 85;

    protected static ?string $title = 'Key Events';

    protected static ?string $navigationLabel = 'Key Events';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('occurred_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'key.room',
                'schedule.requester',
            ]))
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Time')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M j, Y g:i:s A')),
                TextColumn::make('key.room.room_number')
                    ->label('Room')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('key.slot_number')
                    ->label('Key Slot')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        KeyStatus::Used->value => 'danger',
                        KeyStatus::Stored->value => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'iot' => 'info',
                        'synthetic' => 'warning',
                        'manual' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('schedule_info')
                    ->label('Schedule')
                    ->getStateUsing(function (KeyEvent $record): ?string {
                        $schedule = $record->schedule;
                        if (! $schedule) {
                            return null;
                        }

                        $time = $schedule->start_time->format('g:iA').'-'.$schedule->end_time->format('g:iA');

                        return "{$schedule->subject} ({$time})";
                    })
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('schedule.requester.name')
                    ->label('Requester')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M j, Y g:i:s A')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        KeyStatus::Used->value => 'Used',
                        KeyStatus::Stored->value => 'Stored',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'iot' => 'IoT',
                        'synthetic' => 'Synthetic',
                        'manual' => 'Manual',
                    ]),
                SelectFilter::make('room')
                    ->label('Room')
                    ->options(fn () => Room::query()->orderBy('room_number')->pluck('room_number', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $roomId) => $q->whereHas('key', fn (Builder $kq) => $kq->where('room_id', $roomId))
                    ))
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function getTableQuery(): Builder
    {
        return KeyEvent::query();
    }
}
