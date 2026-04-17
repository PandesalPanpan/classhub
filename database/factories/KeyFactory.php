<?php

namespace Database\Factories;

use App\KeyStatus;
use App\Models\Key;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Key>
 */
class KeyFactory extends Factory
{
    protected $model = Key::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'slot_number' => (string) fake()->unique()->numberBetween(1, 9999),
            'status' => KeyStatus::Stored,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => KeyStatus::Disabled]);
    }
}
