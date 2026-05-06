<?php

namespace App\Filament\Pages\Schemas;

use Illuminate\Support\Carbon;

class ScheduleFormOptions
{
    public const APP_EARLIEST_HOUR = 7;

    public const APP_EARLIEST_MINUTE = 30;

    public const APP_LATEST_HOUR = 21;

    public const APP_LATEST_MINUTE = 0;

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
     * @return array<string, string>
     */
    public static function appTimeSlotOptions(): array
    {
        $options = [];
        $time = Carbon::createFromTime(self::APP_EARLIEST_HOUR, self::APP_EARLIEST_MINUTE);
        $end = Carbon::createFromTime(self::APP_LATEST_HOUR, self::APP_LATEST_MINUTE);

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

    /**
     * @return array<int, string>
     */
    public static function appDurationOptions(?string $startTimeSlot = null): array
    {
        $latestEnd = Carbon::createFromTime(self::APP_LATEST_HOUR, self::APP_LATEST_MINUTE);

        if ($startTimeSlot) {
            $start = Carbon::today()->setTimeFromTimeString($startTimeSlot.':00');
            $maxMinutes = max(30, (int) $start->diffInMinutes($latestEnd, absolute: false));
        } else {
            $maxMinutes = 810;
        }

        return static::durationMinutesOptions($maxMinutes);
    }
}
