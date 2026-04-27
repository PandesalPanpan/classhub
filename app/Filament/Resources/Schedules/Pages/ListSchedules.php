<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Livewire\CalendarWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    Radio::make('format')
                        ->label('Choose export format')
                        ->options([
                            'excel' => 'Excel spreadsheet (.xlsx) — plain data with all columns',
                            'signsheet' => 'Sign sheet (.docx) — Word document table with signature column for class representatives',
                        ])
                        ->required()
                        ->default('excel'),
                ])
                ->action(function (array $data): void {
                    session()->put('schedule_export_filters', $this->tableFilters ?? []);
                    $this->redirect(
                        $data['format'] === 'signsheet'
                            ? route('admin.schedule.export.signsheet')
                            : route('admin.schedule.export.excel')
                    );
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
