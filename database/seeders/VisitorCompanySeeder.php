<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VisitorCompany;

class VisitorCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array of visitor company names
        $companies = [
            "AimFlex",
            "Supplier2",
            "Supplier3"
        ];

        // Loop and create each company
        foreach ($companies as $name) {
            VisitorCompany::create([
                'name' => $name,
            ]);
        }
    }
}
