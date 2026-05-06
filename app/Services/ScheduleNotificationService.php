<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;

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
            ->icon('heroicon-o-calendar')
            ->iconColor('info')
            ->title('New schedule request')
            ->body("{$requesterName} requested a schedule for Room {$roomNumber}: {$schedule->subject}")
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->url(URL::signedRoute('schedule.quick-approve', ['schedule' => $schedule->id]))
                    ->markAsRead(),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->url(URL::signedRoute('schedule.quick-reject', ['schedule' => $schedule->id]))
                    ->markAsRead(),
            ]);

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
            ->icon('heroicon-o-calendar')
            ->iconColor('warning')
            ->title('Schedule override requested')
            ->body("{$requesterName} requested an override for Room {$roomNumber}: {$schedule->subject}")
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->url(URL::signedRoute('schedule.quick-approve', ['schedule' => $schedule->id]))
                    ->markAsRead(),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->url(URL::signedRoute('schedule.quick-reject', ['schedule' => $schedule->id]))
                    ->markAsRead(),
            ]);

        $notification->sendToDatabase($notifiedUsers);
        $notification->broadcast($notifiedUsers);
    }
}
