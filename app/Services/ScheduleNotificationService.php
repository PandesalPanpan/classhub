<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Filament\Notifications\Notification;

class ScheduleNotificationService
{
    /**
     * Send notification to users with ReceiveScheduleNotifications permission
     * when a new pending schedule is created.
     */
    public static function notifyPendingCreated(Schedule $schedule): void
    {
        $roomNumber = $schedule->room?->room_number ?? 'N/A';
        $requesterName = $schedule->requester?->name ?? 'Unknown';
        $notifiedUsers = User::permission('ReceiveScheduleNotifications')->get();

        if ($notifiedUsers->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title('New schedule request')
            ->body("{$requesterName} requested a schedule for Room {$roomNumber}: {$schedule->subject}")
            ->success();

        $notification->sendToDatabase($notifiedUsers);
        $notification->broadcast($notifiedUsers);
    }

    /**
     * Send notification to users with ReceiveScheduleNotifications permission
     * when a pending schedule override is requested.
     */
    public static function notifyOverrideRequested(Schedule $schedule): void
    {
        $roomNumber = $schedule->room?->room_number ?? 'N/A';
        $requesterName = $schedule->requester?->name ?? 'Unknown';
        $notifiedUsers = User::permission('ReceiveScheduleNotifications')->get();

        if ($notifiedUsers->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title('Schedule override requested')
            ->body("{$requesterName} requested an override for Room {$roomNumber}: {$schedule->subject}")
            ->warning();

        $notification->sendToDatabase($notifiedUsers);
        $notification->broadcast($notifiedUsers);
    }
}
