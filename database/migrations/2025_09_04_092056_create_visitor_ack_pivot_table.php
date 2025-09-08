<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitor_ack_pivot', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visitor_id')
                  ->constrained('visitors')
                  ->onDelete('cascade');

            $table->foreignId('visitor_staff_acknowledgement_id')
                  ->constrained('visitor_staff_acknowledgements');
                  
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_ack');
    }
};
