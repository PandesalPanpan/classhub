<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RoomUsageWidget extends ChartWidget
{
    protected ?string $heading = 'Most Used Rooms';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '60s';

    public ?string $filter = 'day';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $query = Schedule::query()
            ->with('room')
            ->where('status', ScheduleStatus::Approved)
            ->where('type', ScheduleType::Request);

        [$from, $to] = $this->resolveDateRange();
        if ($from && $to) {
            $query
                ->whereBetween('start_time', [$from, $to]);
        }

        $topRooms = $query
            ->get()
            ->groupBy('room_id')
            ->map(function ($schedules, $roomId): array {
                $firstSchedule = $schedules->first();

                return [
                    'label' => $firstSchedule?->room?->room_number ?? "Room #{$roomId}",
                    'count' => $schedules->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Approved schedule count',
                    'data' => $topRooms->pluck('count')->all(),
                ],
            ],
            'labels' => $topRooms->pluck('label')->all(),
        ];
    }

    /**
     * @return array<scalar, scalar> | null
     */
    protected function getFilters(): ?array
    {
        return [
            'day' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'all' => 'All Time',
        ];
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    protected function resolveDateRange(): array
    {
        return match ($this->filter) {
            'day' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'all' => [null, null],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}
