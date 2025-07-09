<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Purpose;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            "Visit",
            "Interview",
            "Meeting",
            "Delivery"
        ];

        // Loop and create each company
        foreach ($purposes as $name) {
            Purpose::create([
                'name' => $name,
            ]);
        }
    }
}
