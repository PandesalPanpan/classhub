<?php

namespace Database\Factories;

use App\Models\Room;
use App\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => $this->faker->unique()->numberBetween(300, 999),
            'is_active' => true,
            'room_type' => RoomType::Lecture,
            'capacity' => $this->faker->numberBetween(20, 50),
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
