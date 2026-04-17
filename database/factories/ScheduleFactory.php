<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\ScheduleStatus;
use App\ScheduleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $start = now()->addHour()->startOfMinute();
        $end = (clone $start)->addHours(2);

        return [
            'room_id' => Room::factory(),
            'requester_id' => User::factory(),
            'approver_id' => User::factory(),
            'is_priority' => false,
            'subject' => fake()->sentence(3),
            'program_year_section' => 'BSIT 3-1',
            'instructor' => fake()->name(),
            'status' => ScheduleStatus::Pending,
            'type' => ScheduleType::Request,
            'start_time' => $start,
            'end_time' => $end,
            'remarks' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Rejected]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Cancelled]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => ScheduleStatus::Expired]);
    }
}
