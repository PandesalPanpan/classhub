<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Models\Schedule;
use App\ScheduleStatus;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;
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

    /**
     * Get a configured room number column for schedule tables.
     * 
     * @param bool $withFallback If true, shows 'N/A' when room is null
     */
    public static function roomNumber(bool $withFallback = false): TextColumn
    {
        $column = TextColumn::make('room.room_number')
            ->label('Room#')
            ->searchable();

        if ($withFallback) {
            $column->getStateUsing(fn($record) => $record->room?->room_number ?? 'N/A');
        }

        return $column;
    }

    /**
     * Get a configured approver name column for schedule tables.
     */
    public static function approverName(): TextColumn
    {
        return TextColumn::make('approver.name')
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get a configured requester name column for schedule tables.
     */
    public static function requesterName(): TextColumn
    {
        return TextColumn::make('requester.name')
            ->searchable();
    }

    /**
     * Get a configured subject column for schedule tables.
     */
    public static function subject(): TextColumn
    {
        return TextColumn::make('subject')
            ->searchable();
    }

    /**
     * Get a configured program year section column for schedule tables.
     */
    public static function programYearSection(): TextColumn
    {
        return TextColumn::make('program_year_section')
            ->label('PYS')
            ->tooltip('Program Year & Section')
            ->searchable();
    }

    /**
     * Get a configured instructor initials column for schedule tables.
     */
    public static function instructorInitials(): TextColumn
    {
        return TextColumn::make('instructorInitials')
            ->label('Instructor')
            ->searchable();
    }

    /**
     * Get a configured schedule time column for schedule tables.
     * Displays formatted start and end times.
     */
    public static function scheduleTime(): TextColumn
    {
        return TextColumn::make('schedule_time')
            ->label('Schedule')
            ->sortable(query: function ($query, string $direction) {
                return $query->orderBy('start_time', $direction);
            })
            ->getStateUsing(function (Schedule $record): string {
                if (! $record->start_time || ! $record->end_time) {
                    return 'N/A';
                }

                $start = Carbon::parse($record->start_time);
                $end = Carbon::parse($record->end_time);

                // If same day, show date once: "Dec 19, 2025 7:30AM-9:30AM"
                if ($start->isSameDay($end)) {
                    return $start->format('M j, Y') . ' ' . $start->format('g:iA') . '-' . $end->format('g:iA');
                }

                // If different days, show both dates
                return $start->format('M j, Y g:iA') . ' - ' . $end->format('M j, Y g:iA');
            });
    }

    /**
     * Get a configured created_at column for schedule tables.
     */
    public static function createdAt(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA'));
    }

    /**
     * Get a configured updated_at column for schedule tables.
     */
    public static function updatedAt(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true)
            ->formatStateUsing(fn($state) => Carbon::parse($state)->format('M j, Y g:iA'));
    }
}

