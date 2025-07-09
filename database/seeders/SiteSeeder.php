<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Site;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            "Site 1",
            "Site 2",
            "Site 3",
            "Site 4"
        ];

        // Loop and create each company
        foreach ($sites as $name) {
            Site::create([
                'name' => $name,
            ]);
        }
    }
}
