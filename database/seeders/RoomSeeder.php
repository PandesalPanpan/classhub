<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 300; $i <= 313; $i++) {
            Room::firstOrCreate(
                ['room_number' => (string) $i],
                [
                    'is_active' => true,
                ]
            );
        }
    }
}