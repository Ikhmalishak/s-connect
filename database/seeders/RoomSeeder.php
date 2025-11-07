<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            // Site 1
            [
                'site_id' => 1,
                'name' => 'Meeting Room A',
                'capacity' => 8,
                'location' => 'Level 2',
                'status' => 'available',
            ],
            [
                'site_id' => 1,
                'name' => 'Conference Hall',
                'capacity' => 30,
                'location' => 'Level 1',
                'status' => 'available',
            ],

            // Site 2
            [
                'site_id' => 2,
                'name' => 'Training Room',
                'capacity' => 20,
                'location' => 'Block B',
                'status' => 'maintenance',
            ],
            [
                'site_id' => 2,
                'name' => 'Small Meeting Room',
                'capacity' => 6,
                'location' => 'Block B - Level 3',
                'status' => 'available',
            ],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
