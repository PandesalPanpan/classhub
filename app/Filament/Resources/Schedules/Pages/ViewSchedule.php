<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSchedule extends ViewRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('activities')
                ->label('Activity Log')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn (): string => ScheduleResource::getUrl('activities', ['record' => $this->record])),
        ];
    }
}
