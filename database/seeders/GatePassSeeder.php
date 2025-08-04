<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GatePass;

class GatePassSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            GatePass::create([
                'pass_number' => 'V' . str_pad($i, 3, '0', STR_PAD_LEFT), // GP001, GP002, ...
                'pass_type' => 'visitor',
                'state' => 'free',
            ]);
        }

        for ($i = 1; $i <= 30; $i++) {
            GatePass::create([
                'pass_number' => 'D' . str_pad($i, 3, '0', STR_PAD_LEFT), // GP001, GP002, ...
                'pass_type' => 'driver',
                'state' => 'free',
            ]);
        }

        for ($i = 1; $i <= 30; $i++) {
            GatePass::create([
                'pass_number' => 'C' . str_pad($i, 3, '0', STR_PAD_LEFT), // GP001, GP002, ...
                'pass_type' => 'contractor',
                'state' => 'free',
            ]);
        }
    }
}
