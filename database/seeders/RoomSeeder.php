<?php

namespace Database\Seeders;

use App\KeyStatus;
use App\Models\Key;
use App\Models\Room;
use App\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $slotNumber = 1;
        
        for ($i = 300; $i <= 313; $i++) {
            $room = Room::firstOrCreate(
                ['room_number' => (string) $i],
                [
                    'is_active' => true,
                    'room_type' => RoomType::cases()[array_rand(RoomType::cases())]->value,
                    'capacity' => rand(30, 50),
                ]
            );
            
            Key::firstOrCreate(
                ['room_id' => $room->id],
                [
                    'slot_number' => (string) $slotNumber,
                    'status' => KeyStatus::Disabled,
                ]
            );
            
            $slotNumber++;
        }
    }
}