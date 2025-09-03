<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitor_staff_acknowledgements', function (Blueprint $table) {
            $table->id(); // This ID will be used for QR
            $table->json('visitors'); // store visitor info array
            $table->string('acknowledged_by')->nullable();
            $table->string('staff_id')->nullable();
            // $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            // $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_staff_acknowledgements');
    }
};
