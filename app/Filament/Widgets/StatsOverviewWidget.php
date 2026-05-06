<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '30s';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $totalRooms = Room::query()->count();
        $activeRooms = Room::query()->where('is_active', true)->count();
        $todaySchedules = Schedule::query()
            ->where('status', ScheduleStatus::Approved)
            ->where('type', ScheduleType::Request)
            ->whereDate('start_time', today())
            ->count();
        $pendingRequests = Schedule::query()
            ->where('status', ScheduleStatus::Pending)
            ->where('type', ScheduleType::Request)
            ->count();

        return [
            Stat::make('Total Rooms', (string) $totalRooms),
            Stat::make('Active Rooms', (string) $activeRooms),
            Stat::make('Today Approved Schedules', (string) $todaySchedules),
            Stat::make('Pending Requests', (string) $pendingRequests),
        ];
    }
}
