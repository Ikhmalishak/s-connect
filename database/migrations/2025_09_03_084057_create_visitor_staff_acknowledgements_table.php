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

            // Staff acknowledgement
            $table->string('acknowledged_by')->nullable(); // staff name
            $table->string('staff_id')->nullable();        // staff employee ID / badge no
            $table->timestamp('acknowledged_at')->nullable();

            // Security acknowledgement
            $table->string('acknowledged_by_security')->nullable(); // guard/security name
            $table->timestamp('acknowledged_at_security')->nullable();

            // Group reference
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
