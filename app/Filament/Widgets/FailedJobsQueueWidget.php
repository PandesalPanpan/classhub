<?php

namespace App\Filament\Widgets;

use App\Models\FailedQueueJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Artisan;

class FailedJobsQueueWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Failed Jobs')
            ->poll('10s')
            ->query(FailedQueueJob::query()->orderByDesc('failed_at'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('queue')
                    ->sortable(),
                TextColumn::make('job_class')
                    ->label('Job Class')
                    ->searchable(),
                TextColumn::make('failed_at')
                    ->dateTime('M j, Y g:i:s A')
                    ->sortable(),
                TextColumn::make('exception')
                    ->limit(120)
                    ->tooltip(fn (FailedQueueJob $record): string => $record->exception),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (FailedQueueJob $record): void {
                        Artisan::call('queue:retry', ['id' => $record->uuid ?? $record->id]);

                        Notification::make()
                            ->title('Job re-queued')
                            ->success()
                            ->send();
                    }),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (FailedQueueJob $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('Failed job deleted')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
