<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingRequirement;

class ShippingRequirementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requirements = [
            // Americas
            ['region' => 'Americas', 'destination' => 'Canada', 'risk_level' => 'High', 'strength_mm' => '8mm', 'requires_seals' => true],
            ['region' => 'Americas', 'destination' => 'Mexico', 'risk_level' => 'High', 'strength_mm' => '8mm', 'requires_seals' => true],
            ['region' => 'Americas', 'destination' => 'Brazil', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'Americas', 'destination' => 'US**', 'risk_level' => 'High', 'strength_mm' => '8mm', 'requires_seals' => true],

            // APAC
            ['region' => 'APAC', 'destination' => 'Australia', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Cambodia', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'New Zealand', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'India', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Japan', 'risk_level' => 'Medium', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Korea', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Indonesia', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Malaysia', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Philippines', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Singapore', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Taiwan', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'APAC', 'destination' => 'Vietnam', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],

            // EMEA
            ['region' => 'EMEA', 'destination' => 'Austria', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Netherlands', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Cyprus', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Denmark', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Egypt', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'France', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Germany', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Hungary', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Ireland', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Israel', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Italy', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Kuwait', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Poland', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Portugal', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Qatar', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Saudi Arabia', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Spain', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Sweden', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Switzerland', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'Turkey', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'United Arab Emirates (U.A.E)', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'EMEA', 'destination' => 'United Kingdom', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],

            // Greater China
            ['region' => 'Greater China', 'destination' => 'China - All lanes', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
            ['region' => 'Greater China', 'destination' => 'Hong Kong', 'risk_level' => 'Low', 'strength_mm' => '3mm', 'requires_seals' => false],
            ['region' => 'Greater China', 'destination' => 'Taiwan', 'risk_level' => 'Medium', 'strength_mm' => '8mm', 'requires_seals' => false],
        ];

        foreach ($requirements as $requirement) {
            ShippingRequirement::create($requirement);
        }
    }
}
