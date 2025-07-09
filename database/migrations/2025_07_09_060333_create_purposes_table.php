<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function run(): void
    {
        $purposes = [
            "Delivery",
            "Meeting",
            "Interview",
            "Visit"
        ];

        // Loop and create each company
        foreach ($purposes as $name) {
            Purpose::create([
                'name' => $name,
            ]);
        }
    }
};
