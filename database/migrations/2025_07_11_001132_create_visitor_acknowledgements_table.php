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
        Schema::create('visitor_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->string('id_type', 20); // e.g., IC or Passport
            $table->string('id_number', 100);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['id_type', 'id_number']); // prevent duplicate records
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_acknowledgements');
    }
};
