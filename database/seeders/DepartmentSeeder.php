<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->insert([
            ['name' => 'Engineering', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MIS', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Quality', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Production', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Human Resources', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
