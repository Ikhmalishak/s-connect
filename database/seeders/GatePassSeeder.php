<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GatePass;

class GatePassSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 6; $i <= 10; $i++) {
            GatePass::create([
                'pass_number' => 'GP' . str_pad($i, 3, '0', STR_PAD_LEFT), // GP001, GP002, ...
                'pass_type' => 'visitor',
                'state' => 'free',
            ]);
        }
    }
}
