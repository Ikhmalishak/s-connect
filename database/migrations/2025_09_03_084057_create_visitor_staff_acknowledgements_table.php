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
            $table->id();

            // staff who acknowledged the group (nullable until staff picks up)
            $table->string('acknowledged_by')->nullable(); // staff name
            $table->string('staff_id')->nullable();        // staff employee ID / badge no
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('ack_number')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_staff_acknowledgements');
    }
};
