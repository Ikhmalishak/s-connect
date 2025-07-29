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
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gate_pass_id')->nullable()->constrained('gate_passes');
            $table->string('visitor_name');
            $table->string('visitor_type');
            $table->string('vehicle_number')->nullable();
            $table->string('site');
            $table->time('time_register')->nullable();
            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->integer('duration')->nullable();
            $table->string('visitor_company')->nullable();
            $table->string('purpose');
            $table->string('remarks')->nullable();
            $table->string('ic_number')->nullable();
            $table->string('passport')->nullable();
            $table->string('phone_number');
            $table->boolean('is_acknowledge')->default(false); // did they watch the video etc.
            $table->string('person_to_meet')->nullable(); // for meetings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
