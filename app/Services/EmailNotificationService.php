<?php

namespace App\Services;

use App\Mail\Key\KeyMissing;
use App\Mail\Schedule\HandoverConfirmationRequested;
use App\Mail\Schedule\HandoverKeyMissingRequester;
use App\Mail\Schedule\ScheduleApproved;
use App\Mail\Schedule\ScheduleCancelledConfirmation;
use App\Mail\Schedule\ScheduleCreatedConfirmation;
use App\Mail\Schedule\ScheduleExpired;
use App\Mail\Schedule\ScheduleOverridePendingConfirmation;
use App\Mail\Schedule\ScheduleRejected;
use App\Mail\User\WelcomeEmail;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public static function sendScheduleCreatedConfirmation(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleCreatedConfirmation($schedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendScheduleOverridePendingConfirmation(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleOverridePendingConfirmation($schedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendScheduleApproved(Schedule $schedule, ?Schedule $nextSchedule = null): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleApproved($schedule, $nextSchedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendScheduleRejected(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleRejected($schedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendScheduleCancelledConfirmation(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleCancelledConfirmation($schedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendScheduleExpired(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new ScheduleExpired($schedule),
            ['schedule_id' => $schedule->id]
        );
    }

    public static function sendKeyMissing(Schedule $lastSchedule): void
    {
        $adminUsers = User::role(['Admin', 'Superadmin'])->get();

        if ($adminUsers->isEmpty()) {
            Log::warning('[EmailNotificationService] No admin users found for key-missing email', [
                'schedule_id' => $lastSchedule->id,
            ]);

            return;
        }

        foreach ($adminUsers as $admin) {
            if (! $admin->email) {
                continue;
            }

            self::sendTo(
                $admin->email,
                new KeyMissing($lastSchedule, $admin),
                [
                    'schedule_id' => $lastSchedule->id,
                    'admin_id' => $admin->id,
                ],
                false
            );
        }
    }

    public static function sendWelcomeEmail(User $user): void
    {
        self::sendIfHasEmail(
            $user->email,
            new WelcomeEmail($user),
            ['user_id' => $user->id]
        );
    }

    public static function sendHandoverConfirmationRequested(ScheduleHandover $handover): void
    {
        $handover->loadMissing(['previousSchedule.requester', 'nextSchedule.requester']);

        $previousRequester = $handover->previousSchedule?->requester;
        $nextRequester = $handover->nextSchedule?->requester;

        if ($previousRequester?->email) {
            self::sendTo(
                $previousRequester->email,
                new HandoverConfirmationRequested($handover, 'previous'),
                ['handover_id' => $handover->id],
                false
            );
        }

        if ($nextRequester?->email) {
            self::sendTo(
                $nextRequester->email,
                new HandoverConfirmationRequested($handover, 'next'),
                ['handover_id' => $handover->id],
                false
            );
        }
    }

    public static function sendHandoverKeyMissingToRequester(Schedule $schedule): void
    {
        self::sendIfHasEmail(
            $schedule->requester?->email,
            new HandoverKeyMissingRequester($schedule),
            ['schedule_id' => $schedule->id],
            false
        );
    }

    public static function sendHandoverDisputeAlert(ScheduleHandover $handover): void
    {
        $handover->loadMissing(['previousSchedule', 'nextSchedule']);

        $adminUsers = User::role(['Admin', 'Superadmin'])->get();
        if ($adminUsers->isEmpty()) {
            Log::warning('[EmailNotificationService] No admin users found for handover dispute alert', [
                'handover_id' => $handover->id,
            ]);

            return;
        }

        foreach ($adminUsers as $admin) {
            if (! $admin->email || ! $handover->previousSchedule) {
                continue;
            }

            self::sendTo(
                $admin->email,
                new KeyMissing($handover->previousSchedule, $admin),
                ['handover_id' => $handover->id, 'admin_id' => $admin->id],
                false
            );
        }
    }

    private static function sendIfHasEmail(
        ?string $email,
        Mailable $mailable,
        array $context = [],
        bool $throw = true
    ): void {
        if (! $email) {
            Log::warning('[EmailNotificationService] Skipping send due to missing email', [
                'mailable' => $mailable::class,
                ...$context,
            ]);

            return;
        }

        self::sendTo($email, $mailable, $context, $throw);
    }

    private static function sendTo(
        string $email,
        Mailable $mailable,
        array $context = [],
        bool $throw = true
    ): void {
        try {
            Mail::to($email)->send($mailable);

            Log::info('[EmailNotificationService] Sent email', [
                'to' => $email,
                'mailable' => $mailable::class,
                ...$context,
            ]);
        } catch (\Throwable $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $email,
                'mailable' => $mailable::class,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                ...$context,
            ]);

            if ($throw) {
                throw $e;
            }
        }
    }
}
