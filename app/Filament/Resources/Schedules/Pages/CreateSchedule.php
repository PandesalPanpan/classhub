<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\KeyEvent;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected ?int $pendingLinkKeyEventId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingLinkKeyEventId = $data['link_key_event_id'] ?? null;
        unset($data['link_key_event_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingLinkKeyEventId) {
            KeyEvent::where('id', $this->pendingLinkKeyEventId)
                ->whereNull('schedule_id')
                ->update(['schedule_id' => $this->record->id]);
        }
    }
}
