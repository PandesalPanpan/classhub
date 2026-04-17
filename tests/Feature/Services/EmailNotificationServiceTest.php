<?php

namespace Tests\Feature\Services;

use App\Mail\Key\KeyMissing;
use App\Mail\Schedule\HandoverConfirmationRequested;
use App\Mail\Schedule\HandoverKeyMissingRequester;
use App\Mail\Schedule\ScheduleApproved;
use App\Mail\Schedule\ScheduleCancelledConfirmation;
use App\Mail\Schedule\ScheduleCreatedConfirmation;
use App\Mail\Schedule\ScheduleExpired;
use App\Mail\Schedule\ScheduleRejected;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_schedule_created_confirmation_email(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendScheduleCreatedConfirmation($schedule);

        Mail::assertQueued(ScheduleCreatedConfirmation::class);
    }

    public function test_does_not_send_if_requester_has_no_email(): void
    {
        Mail::fake();

        $schedule = $this->makeSchedule();
        $schedule->setRelation('requester', new User(['email' => null]));

        EmailNotificationService::sendScheduleCreatedConfirmation($schedule);

        Mail::assertNothingQueued();
    }

    public function test_sends_schedule_approved_email(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendScheduleApproved($schedule);

        Mail::assertQueued(ScheduleApproved::class);
    }

    public function test_sends_schedule_approved_email_with_next_schedule_hint(): void
    {
        Mail::fake();

        $room = Room::factory()->create();
        $schedule = $this->makeSchedule(['room_id' => $room->id]);
        $nextSchedule = $this->makeSchedule([
            'room_id' => $room->id,
            'start_time' => $schedule->end_time->copy()->addMinutes(5),
            'end_time' => $schedule->end_time->copy()->addMinutes(65),
        ]);

        EmailNotificationService::sendScheduleApproved($schedule, $nextSchedule);

        Mail::assertQueued(ScheduleApproved::class, function (ScheduleApproved $mail) use ($nextSchedule) {
            return $mail->nextSchedule?->id === $nextSchedule->id;
        });
    }

    public function test_sends_schedule_rejected_email(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendScheduleRejected($schedule);

        Mail::assertQueued(ScheduleRejected::class);
    }

    public function test_sends_schedule_cancelled_email(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendScheduleCancelledConfirmation($schedule);

        Mail::assertQueued(ScheduleCancelledConfirmation::class);
    }

    public function test_sends_schedule_expired_email(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendScheduleExpired($schedule);

        Mail::assertQueued(ScheduleExpired::class);
    }

    public function test_sends_key_missing_to_admins(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        $this->seedAdminUsers(2);

        EmailNotificationService::sendKeyMissing($schedule);

        $this->assertCount(2, Mail::queued(KeyMissing::class));
    }

    public function test_sends_handover_confirmation_to_both_parties(): void
    {
        Mail::fake();

        $handover = ScheduleHandover::factory()->create();

        EmailNotificationService::sendHandoverConfirmationRequested($handover);

        $this->assertCount(2, Mail::queued(HandoverConfirmationRequested::class));
    }

    public function test_sends_handover_key_missing_to_requester(): void
    {
        Mail::fake();
        $schedule = $this->makeSchedule();

        EmailNotificationService::sendHandoverKeyMissingToRequester($schedule);

        Mail::assertQueued(HandoverKeyMissingRequester::class);
    }

    public function test_sends_handover_dispute_alert_to_admins(): void
    {
        Mail::fake();
        $handover = ScheduleHandover::factory()->create();

        $this->seedAdminUsers(1);

        EmailNotificationService::sendHandoverDisputeAlert($handover);

        Mail::assertQueued(KeyMissing::class);
    }

    private function makeSchedule(array $overrides = []): Schedule
    {
        return Schedule::factory()->create($overrides);
    }

    private function seedAdminUsers(int $count): void
    {
        Role::findOrCreate('Admin');

        User::factory()->count($count)->create()->each(function (User $user): void {
            $user->assignRole('Admin');
        });
    }
}
