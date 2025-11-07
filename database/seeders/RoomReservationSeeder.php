<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomReservationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('room_reservations')->insert([
            [
                'room_id'    => 1,
                'user_name'  => 'John Doe',
                'user_id'    => 'EMP1001',
                'start_time' => Carbon::parse('2025-11-03 09:00:00'),
                'end_time'   => Carbon::parse('2025-11-03 10:00:00'),
                'date'       => Carbon::parse('2025-11-03'),
                'purpose'    => 'Team Meeting',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id'    => 1,
                'user_name'  => 'Jane Smith',
                'user_id'    => 'EMP1002',
                'start_time' => Carbon::parse('2025-11-03 14:00:00'),
                'end_time'   => Carbon::parse('2025-11-03 15:30:00'),
                'date'       => Carbon::parse('2025-11-03'),
                'purpose'    => 'Client Discussion',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'room_id'    => 2,
                'user_name'  => 'Michael Lee',
                'user_id'    => 'EMP1003',
                'start_time' => Carbon::parse('2025-11-03 11:00:00'),
                'end_time'   => Carbon::parse('2025-11-03 12:00:00'),
                'date'       => Carbon::parse('2025-11-03'),
                'purpose'    => 'Internal Training',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
