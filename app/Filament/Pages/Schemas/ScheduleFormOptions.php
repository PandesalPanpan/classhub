<?php

namespace App\Filament\Pages\Schemas;

use Illuminate\Support\Carbon;

class ScheduleFormOptions
{
    /**
     * @return array<string, string>
     */
    public static function timeSlotOptions(): array
    {
        $options = [];
        $time = Carbon::createFromTime(6, 0);
        $end = Carbon::createFromTime(22, 0);

        while ($time <= $end) {
            $options[$time->format('H:i')] = $time->format('g:i A');
            $time->addMinutes(30);
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function durationMinutesOptions(int $maxMinutes = 810): array
    {
        $options = [];
        foreach (range(30, $maxMinutes, 30) as $minutes) {
            $options[$minutes] = match (true) {
                $minutes < 60 => "{$minutes} minutes",
                $minutes % 60 === 0 => (int) ($minutes / 60).' '.str('hour')->plural((int) ($minutes / 60)),
                default => (int) ($minutes / 60).'.5 hours',
            };
        }

        return $options;
    }
}
