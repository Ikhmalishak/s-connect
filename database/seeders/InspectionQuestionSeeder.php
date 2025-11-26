<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InspectionQuestion;

class InspectionQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            // Outside
            ['question' => 'Outside - No hole/crack/unusual repair work'],
            ['question' => 'Outside - No defective door locking'],
            ['question' => 'Outside - No adhesive label from previous cargo'],
            ['question' => 'Outside - Closing devices are in good condition'],
            ['question' => 'Outside - No roof leaking mechanism'],
            ['question' => 'Outside - Outer shell including walls/undercarriage are good'],

            // Inside
            ['question' => 'Inside - Is there any hole or crack'],
            ['question' => 'Inside - Is the container clean'],
            ['question' => 'Inside - Is there any bend or dent'],
            ['question' => 'Inside - Is there any cargo residue'],
            ['question' => 'Inside - Is the container in dry condition'],
            ['question' => 'Inside - Is the container free from insects'],
            ['question' => 'Inside - Closed door inspection for visible light'],
            ['question' => 'Inside - Any light passing through door gasket'],
            ['question' => 'Inside - Weather stripping gasket intact'],
            ['question' => 'Inside - Is there any odour present'],
            ['question' => 'Inside - Are ventilation holes taped prior to loading'],

            // Special
            ['question' => 'Inside - Photo of stuffing process (3 stages)'],

            // Warehouse judgement
            ['question' => 'Warehouse Judgement'],

            // Container acknowledgement
            ['question' => 'Container Acknowledgement'],
        ];

        foreach ($questions as $q) {
            InspectionQuestion::create($q);
        }
    }
}
