<?php

namespace App\Services;

use App\Models\Schedule;
use App\ScheduleStatus;
use Carbon\Carbon;

class ScheduleOverlapChecker
{
    /**
    * Determine if a room has any overlapping schedules within the given window.
    *
    * @param int $roomId
    * @param Carbon $start
    * @param Carbon $end
    * @param array<int,\App\ScheduleStatus> $statuses
    * @param int|null $excludeId Optional schedule ID to exclude (for edits)
    */
    public static function hasOverlap(
        int $roomId,
        Carbon $start,
        Carbon $end,
        array $statuses = [ScheduleStatus::Approved, ScheduleStatus::Pending],
        ?int $excludeId = null
    ): bool {
        return Schedule::query()
            ->where('room_id', $roomId)
            ->whereIn('status', $statuses)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();
    }
}
