<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\RequestScheduleForm;
use App\Models\Schedule;
use App\ScheduleStatus;
use Carbon\Carbon;
use Filament\Actions\Action;
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
                        
                        Schedule::create($data);
                        
                        // Trigger calendar refresh to show the new pending request
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
