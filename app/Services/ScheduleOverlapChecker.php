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
     * @param  array<int,\App\ScheduleStatus>  $statuses
     * @param  int|null  $excludeId  Optional schedule ID to exclude (for edits)
     * @param  array<int>  $excludeIds  Optional schedule IDs to exclude (e.g. template when creating override)
     */
    public static function hasOverlap(
        int $roomId,
        Carbon $start,
        Carbon $end,
        array $statuses = [ScheduleStatus::Approved, ScheduleStatus::Pending],
        ?int $excludeId = null,
        array $excludeIds = []
    ): bool {
        $exclude = array_filter(array_merge($excludeId ? [$excludeId] : [], $excludeIds));

        return Schedule::query()
            ->where('room_id', $roomId)
            ->whereIn('status', $statuses)
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();
    }

    /**
     * Check for conflicts between multiple time ranges and existing schedules.
     * Returns a map of conflicting schedules keyed by their time ranges.
     *
     * @param  array<int, array{start_time: Carbon, end_time: Carbon}>  $timeRanges  Array of time ranges to check
     * @param  array<int,\App\ScheduleStatus>  $statuses
     * @return array<string, \App\Models\Schedule> Map of 'start_time-end_time' => Schedule model
     */
    public static function checkBatchConflicts(
        int $roomId,
        array $timeRanges,
        array $statuses = [ScheduleStatus::Approved, ScheduleStatus::Pending]
    ): array {
        if (empty($timeRanges)) {
            return [];
        }

        // Find the overall date range for efficient querying
        $allStarts = collect($timeRanges)->pluck('start_time')->all();
        $allEnds = collect($timeRanges)->pluck('end_time')->all();
        $minStart = collect($allStarts)->min();
        $maxEnd = collect($allEnds)->max();

        // Single batch query to get all potentially conflicting schedules
        $conflictingSchedules = Schedule::query()
            ->where('room_id', $roomId)
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($minStart, $maxEnd) {
                $query->where('start_time', '<', $maxEnd)
                    ->where('end_time', '>', $minStart);
            })
            ->get(['id', 'start_time', 'end_time', 'subject', 'instructor', 'program_year_section']);

        // Map conflicts by time range
        $conflicts = [];

        foreach ($timeRanges as $timeRange) {
            $rangeStart = $timeRange['start_time'];
            $rangeEnd = $timeRange['end_time'];
            $rangeKey = $rangeStart->toIso8601String().'-'.$rangeEnd->toIso8601String();

            foreach ($conflictingSchedules as $existingSchedule) {
                if ($rangeStart < $existingSchedule->end_time && $rangeEnd > $existingSchedule->start_time) {
                    $conflicts[$rangeKey] = $existingSchedule;
                    break; // Found a conflict, no need to check more for this range
                }
            }
        }

        return $conflicts;
    }
}
