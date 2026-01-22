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
        
        $rooms = [
            'CEA302',
            'CEA300',
            'CEA316',
            'CEA315',
            'CEA314',
            'CEA313',
            'CEA312',
            'CEA311',
            'CEA310',
            'CEA413',
            'CEA207',
        ];
        
        foreach ($rooms as $roomNumber) {
            $room = Room::firstOrCreate(
                ['room_number' => $roomNumber],
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