<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\ScheduleHandover;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduleHandover>
 */
class ScheduleHandoverFactory extends Factory
{
    protected $model = ScheduleHandover::class;

    public function definition(): array
    {
        $previous = Schedule::factory()->approved();
        $next = Schedule::factory()->approved();

        return [
            'previous_schedule_id' => $previous,
            'next_schedule_id' => $next,
            'previous_confirmed_at' => null,
            'next_confirmed_at' => null,
            'previous_disputed_at' => null,
            'next_disputed_at' => null,
            'resolution_deadline_at' => now()->addMinutes(15),
            'resolution_finalized_at' => null,
        ];
    }

    public function bothConfirmed(): static
    {
        return $this->state(fn () => [
            'previous_confirmed_at' => now()->subMinute(),
            'next_confirmed_at' => now(),
        ]);
    }
}
