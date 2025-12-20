<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\ScheduleStatus;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;

class ScheduleColumns
{
    /**
     * Get a configured status column for schedule tables.
     */
    public static function status(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->formatStateUsing(function (ScheduleStatus|string|null $state): string {
                if (! $state) {
                    return 'Unknown';
                }

                $value = $state instanceof ScheduleStatus ? $state->value : $state;

                return Str::title(strtolower($value)); // e.g. "PENDING" -> "Pending"
            })
            ->color(function (ScheduleStatus|string|null $state): string {
                if (! $state) {
                    return 'secondary';
                }

                $status = $state instanceof ScheduleStatus ? $state : ScheduleStatus::from($state);

                return match ($status) {
                    ScheduleStatus::Pending => 'warning',
                    ScheduleStatus::Approved => 'success',
                    ScheduleStatus::Rejected => 'danger',
                    ScheduleStatus::Cancelled => 'secondary',
                    ScheduleStatus::Completed => 'success',
                    ScheduleStatus::Expired => 'secondary',
                };
            })
            ->searchable();
    }
}

