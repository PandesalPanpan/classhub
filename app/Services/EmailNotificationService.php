<?php

namespace App\Services;

use App\Mail\Key\KeyMissing;
use App\Mail\Schedule\HandoverAssumed;
use App\Mail\Schedule\KeyReadyForPickup;
use App\Mail\Schedule\KeyWithPreviousUser;
use App\Mail\Schedule\ScheduleApproved;
use App\Mail\Schedule\ScheduleCancelledConfirmation;
use App\Mail\Schedule\ScheduleCreatedConfirmation;
use App\Mail\Schedule\ScheduleExpired;
use App\Mail\Schedule\ScheduleOverridePendingConfirmation;
use App\Mail\Schedule\ScheduleRejected;
use App\Mail\User\WelcomeEmail;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Send schedule created confirmation to the requester.
     */
    public static function sendScheduleCreatedConfirmation(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleCreatedConfirmation called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
            'subject' => $schedule->subject,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleCreatedConfirmation email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleCreatedConfirmation($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleCreatedConfirmation::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleCreatedConfirmation::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send schedule override pending confirmation to the requester.
     */
    public static function sendScheduleOverridePendingConfirmation(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleOverridePendingConfirmation called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
            'subject' => $schedule->subject,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleOverridePendingConfirmation email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleOverridePendingConfirmation($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleOverridePendingConfirmation::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleOverridePendingConfirmation::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send schedule approved notification to the requester.
     */
    public static function sendScheduleApproved(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleApproved called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleApproved email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleApproved($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleApproved::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleApproved::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send schedule rejected notification to the requester.
     */
    public static function sendScheduleRejected(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleRejected called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleRejected email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleRejected($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleRejected::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleRejected::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send schedule cancelled confirmation to the requester.
     */
    public static function sendScheduleCancelledConfirmation(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleCancelledConfirmation called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleCancelledConfirmation email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleCancelledConfirmation($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleCancelledConfirmation::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleCancelledConfirmation::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send schedule expired notification to the requester.
     */
    public static function sendScheduleExpired(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendScheduleExpired called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending ScheduleExpired email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new ScheduleExpired($schedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleExpired::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => ScheduleExpired::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send key missing notification to admins.
     */
    public static function sendKeyMissing(Schedule $lastSchedule): void
    {
        Log::info('[EmailNotificationService] sendKeyMissing called', [
            'schedule_id' => $lastSchedule->id,
            'room_id' => $lastSchedule->room_id,
        ]);

        $adminUsers = User::role(['Admin', 'Superadmin'])->get();
        Log::info('[EmailNotificationService] Found admin users', [
            'count' => $adminUsers->count(),
            'admin_emails' => $adminUsers->pluck('email')->toArray(),
        ]);

        if ($adminUsers->isEmpty()) {
            Log::warning('[EmailNotificationService] No admin users found');

            return;
        }

        foreach ($adminUsers as $admin) {
            if ($admin->email) {
                try {
                    Log::info('[EmailNotificationService] Sending KeyMissing email', [
                        'to' => $admin->email,
                        'admin_id' => $admin->id,
                    ]);

                    Mail::to($admin->email)
                        ->send(new KeyMissing($lastSchedule, $admin));

                    Log::info('[EmailNotificationService] Email queued successfully', [
                        'to' => $admin->email,
                        'mailable' => KeyMissing::class,
                    ]);
                } catch (\Exception $e) {
                    Log::error('[EmailNotificationService] Failed to send email', [
                        'to' => $admin->email,
                        'mailable' => KeyMissing::class,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
    }

    /**
     * Send welcome email to a new user.
     */
    public static function sendWelcomeEmail(User $user): void
    {
        Log::info('[EmailNotificationService] sendWelcomeEmail called', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
        ]);

        if (! $user->email) {
            Log::warning('[EmailNotificationService] No user email found', ['user_id' => $user->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending WelcomeEmail', [
                'to' => $user->email,
            ]);

            Mail::to($user->email)
                ->send(new WelcomeEmail($user));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $user->email,
                'mailable' => WelcomeEmail::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $user->email,
                'mailable' => WelcomeEmail::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send key ready for pickup reminder to the requester.
     */
    public static function sendKeyReadyForPickup(Schedule $schedule, ?Schedule $nextSchedule = null): void
    {
        Log::info('[EmailNotificationService] sendKeyReadyForPickup called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
            'has_next_schedule' => $nextSchedule !== null,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending KeyReadyForPickup email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new KeyReadyForPickup($schedule, $nextSchedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => KeyReadyForPickup::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => KeyReadyForPickup::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send key with previous user notification to the requester.
     */
    public static function sendKeyWithPreviousUser(Schedule $schedule, Schedule $previousSchedule): void
    {
        Log::info('[EmailNotificationService] sendKeyWithPreviousUser called', [
            'schedule_id' => $schedule->id,
            'previous_schedule_id' => $previousSchedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending KeyWithPreviousUser email', [
                'to' => $schedule->requester->email,
            ]);

            Mail::to($schedule->requester->email)
                ->send(new KeyWithPreviousUser($schedule, $previousSchedule));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => KeyWithPreviousUser::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => KeyWithPreviousUser::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send key missing reminder to the requester when key status is Missing
     * before their class starts.
     */
    public static function sendKeyMissingReminder(Schedule $schedule): void
    {
        Log::info('[EmailNotificationService] sendKeyMissingReminder called', [
            'schedule_id' => $schedule->id,
            'requester_id' => $schedule->requester_id,
            'requester_email' => $schedule->requester?->email,
        ]);

        if (! $schedule->requester?->email) {
            Log::warning('[EmailNotificationService] No requester email found', ['schedule_id' => $schedule->id]);

            return;
        }

        try {
            Log::info('[EmailNotificationService] Sending KeyMissingReminder email', [
                'to' => $schedule->requester->email,
            ]);

            // Reuse the KeyMissing mailable — it's addressed to the requester
            // rather than admins, but the template shows the same info.
            Mail::to($schedule->requester->email)
                ->send(new KeyMissing($schedule, $schedule->requester));

            Log::info('[EmailNotificationService] Email queued successfully', [
                'to' => $schedule->requester->email,
                'mailable' => KeyMissing::class,
            ]);
        } catch (\Exception $e) {
            Log::error('[EmailNotificationService] Failed to send email', [
                'to' => $schedule->requester->email,
                'mailable' => KeyMissing::class,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Send handover assumed notification to both parties when the system
     * detects that the key was not returned but the next schedule is in
     * the handover window. This is an assumption — not a confirmation.
     * Both parties can correct the system if it's wrong.
     */
    public static function sendHandoverAssumed(Schedule $previousSchedule, Schedule $nextSchedule): void
    {
        Log::info('[EmailNotificationService] sendHandoverAssumed called', [
            'previous_schedule_id' => $previousSchedule->id,
            'next_schedule_id' => $nextSchedule->id,
        ]);

        // Notify the previous schedule's requester
        if ($previousSchedule->requester?->email) {
            try {
                Log::info('[EmailNotificationService] Sending HandoverAssumed to previous requester', [
                    'to' => $previousSchedule->requester->email,
                ]);

                Mail::to($previousSchedule->requester->email)
                    ->send(new HandoverAssumed($previousSchedule, $nextSchedule, 'previous'));

                Log::info('[EmailNotificationService] Email queued successfully', [
                    'to' => $previousSchedule->requester->email,
                    'mailable' => HandoverAssumed::class,
                ]);
            } catch (\Exception $e) {
                Log::error('[EmailNotificationService] Failed to send email', [
                    'to' => $previousSchedule->requester->email,
                    'mailable' => HandoverAssumed::class,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Notify the next schedule's requester
        if ($nextSchedule->requester?->email) {
            try {
                Log::info('[EmailNotificationService] Sending HandoverAssumed to next requester', [
                    'to' => $nextSchedule->requester->email,
                ]);

                Mail::to($nextSchedule->requester->email)
                    ->send(new HandoverAssumed($previousSchedule, $nextSchedule, 'next'));

                Log::info('[EmailNotificationService] Email queued successfully', [
                    'to' => $nextSchedule->requester->email,
                    'mailable' => HandoverAssumed::class,
                ]);
            } catch (\Exception $e) {
                Log::error('[EmailNotificationService] Failed to send email', [
                    'to' => $nextSchedule->requester->email,
                    'mailable' => HandoverAssumed::class,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
