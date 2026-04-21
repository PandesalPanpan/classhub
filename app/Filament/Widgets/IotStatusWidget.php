<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class IotStatusWidget extends Widget
{
    protected static ?string $heading = 'IoT Device Status';

    protected string $view = 'filament.widgets.iot-status-widget';

    protected static ?int $sort = 2;

    protected static string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $lastPingIso = Cache::get('iot_last_ping_at');
        $lastPing = $lastPingIso ? Carbon::parse($lastPingIso) : null;

        $isActive = $lastPing !== null && $lastPing->greaterThanOrEqualTo(now()->subMinutes(2));

        return [
            'isActive' => $isActive,
            'lastSeen' => $lastPing?->diffForHumans(),
            'lastSeenAt' => $lastPing?->format('M j, Y g:i:s A'),
        ];
    }
}
