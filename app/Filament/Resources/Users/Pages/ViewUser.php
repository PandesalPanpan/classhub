<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('viewActivities')
                ->label('Activity Log')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->url(fn (): string => UserResource::getUrl('activities', ['record' => $this->record])),
        ];
    }
}
