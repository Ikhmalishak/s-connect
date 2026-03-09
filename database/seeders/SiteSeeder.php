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
            ['name' => 'Site 1', 'site_code' => 'S1'],
            ['name' => 'Site 2', 'site_code' => 'S2'],
            ['name' => 'Site 3', 'site_code' => 'S3'],
            ['name' => 'Site 4', 'site_code' => 'S4'],
            ['name' => 'SKPBM S5', 'site_code' => 'S5'],
        ];

        foreach ($sites as $site) {
            Site::create($site);
        }
    }

}
