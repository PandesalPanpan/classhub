<?php

namespace Database\Factories;

use App\KeyStatus;
use App\Models\Key;
use App\Models\KeyEvent;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KeyEvent>
 */
class KeyEventFactory extends Factory
{
    protected $model = KeyEvent::class;

    public function definition(): array
    {
        return [
            'key_id' => Key::factory(),
            'schedule_id' => Schedule::factory(),
            'status' => KeyStatus::Used->value,
            'occurred_at' => now(),
            'source' => 'iot',
        ];
    }

    public function stored(): static
    {
        return $this->state(fn () => ['status' => KeyStatus::Stored->value]);
    }

    public function synthetic(): static
    {
        return $this->state(fn () => ['source' => 'synthetic']);
    }
}
