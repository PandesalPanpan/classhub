<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EndOfClassJob;
use App\Jobs\PostClassCheckJob;
use App\Jobs\VerifyScheduleKeyUsageJob;
use App\KeyStatus;
use App\Models\Key;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleHandover;
use App\Services\HandoverOperationalService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DuplicateJobPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_job_unique_id_is_stable_for_same_schedule(): void
    {
        [$schedule] = $this->makeApprovedScheduleWithKey();
        $secondSchedule = Schedule::factory()->approved()->create([
            'room_id' => $schedule->room_id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $firstDispatch = new VerifyScheduleKeyUsageJob($schedule);
        $duplicateDispatch = new VerifyScheduleKeyUsageJob($schedule);
        $differentScheduleDispatch = new VerifyScheduleKeyUsageJob($secondSchedule);

        $this->assertSame($firstDispatch->uniqueId(), $duplicateDispatch->uniqueId());
        $this->assertNotSame($firstDispatch->uniqueId(), $differentScheduleDispatch->uniqueId());
    }

    public function test_handover_apply_does_not_enqueue_duplicate_followup_jobs(): void
    {
        Queue::fake();

        $handover = $this->makeHandoverWithSharedRoom();

        HandoverOperationalService::apply($handover);

        Queue::assertNothingPushed();
    }

    public function test_job_unique_configuration_is_schedule_scoped(): void
    {
        [$schedule] = $this->makeApprovedScheduleWithKey();

        $verify = new VerifyScheduleKeyUsageJob($schedule);
        $endOfClass = new EndOfClassJob($schedule);
        $postClass = new PostClassCheckJob($schedule);

        $this->assertContains(ShouldBeUniqueUntilProcessing::class, class_implements($verify));
        $this->assertContains(ShouldBeUniqueUntilProcessing::class, class_implements($endOfClass));
        $this->assertContains(ShouldBeUniqueUntilProcessing::class, class_implements($postClass));

        $this->assertSame("verify_key_usage:{$schedule->id}", $verify->uniqueId());
        $this->assertSame("end_of_class:{$schedule->id}", $endOfClass->uniqueId());
        $this->assertSame("post_class_check:{$schedule->id}", $postClass->uniqueId());
    }

    private function makeApprovedScheduleWithKey(): array
    {
        $room = Room::factory()->create();
        $schedule = Schedule::withoutEvents(function () use ($room) {
            return Schedule::factory()->approved()->create([
                'room_id' => $room->id,
                'start_time' => now()->subMinutes(40),
                'end_time' => now()->addMinutes(20),
            ]);
        });

        Key::factory()->create([
            'room_id' => $room->id,
            'status' => KeyStatus::Used,
        ]);

        return [$schedule];
    }

    private function makeHandoverWithSharedRoom(): ScheduleHandover
    {
        $room = Room::factory()->create();
        Key::factory()->create(['room_id' => $room->id, 'status' => KeyStatus::Used]);

        [$previous, $next] = Schedule::withoutEvents(function () use ($room) {
            $previous = Schedule::factory()->approved()->create([
                'room_id' => $room->id,
                'start_time' => now()->subHours(2),
                'end_time' => now()->subHour(),
            ]);

            $next = Schedule::factory()->approved()->create([
                'room_id' => $room->id,
                'start_time' => now()->addMinutes(20),
                'end_time' => now()->addHours(2),
            ]);

            return [$previous, $next];
        });

        return ScheduleHandover::factory()->create([
            'previous_schedule_id' => $previous->id,
            'next_schedule_id' => $next->id,
        ]);
    }
}
