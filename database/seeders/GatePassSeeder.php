<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GatePass;
use App\Models\Site;

class GatePassSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all sites
        $sites = Site::all();

        foreach ($sites as $site) {

            // Visitor passes V001-V030
            for ($i = 1; $i <= 30; $i++) {
                GatePass::create([
                    'pass_number' => 'V' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'pass_type' => 'visitor',
                    'site_id' => $site->id,
                    'state' => 'free',
                ]);
            }

            // Driver passes D001-D030
            for ($i = 1; $i <= 30; $i++) {
                GatePass::create([
                    'pass_number' => 'D' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'pass_type' => 'driver',
                    'site_id' => $site->id,
                    'state' => 'free',
                ]);
            }

            // Contractor passes C001-C030
            for ($i = 1; $i <= 30; $i++) {
                GatePass::create([
                    'pass_number' => 'C' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'pass_type' => 'contractor',
                    'site_id' => $site->id,
                    'state' => 'free',
                ]);
            }
        }
    }
}
