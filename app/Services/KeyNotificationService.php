<?php

namespace App\Services;

use App\KeyStatus;
use App\Models\Key;
use App\Models\User;
use Filament\Notifications\Notification;

class KeyNotificationService
{
    /**
     * Send notification to users with ReceiveKeyNotifications permission
     * when a key status is updated.
     */
    public static function notifyKeyStatusUpdated(Key $key, KeyStatus $oldStatus, KeyStatus $newStatus): void
    {
        $roomNumber = $key->room?->room_number ?? 'N/A';
        $notifiedUsers = User::permission('ReceiveKeyNotifications')->get();

        if ($notifiedUsers->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->icon('heroicon-o-key')
            ->iconColor($newStatus === KeyStatus::Missing ? 'danger' : 'success')
            ->title('Key status updated')
            ->body("Slot {$key->slot_number}, Room {$roomNumber}: {$oldStatus->value} → {$newStatus->value}");

        $notification->sendToDatabase($notifiedUsers);
        $notification->broadcast($notifiedUsers);
    }
}
