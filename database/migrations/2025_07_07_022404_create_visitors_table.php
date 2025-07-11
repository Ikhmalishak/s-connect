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
            $table->string('visitor_name');
            $table->string('vehicle_number')->nullable();
            $table->string('site');
            $table->time('time_register')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->foreignId('visitor_company_id')->constrained('visitor_companies');
            $table->string('purpose');
            $table->string('remarks')->nullable();
            $table->string('ic_number')->nullable();
            $table->string('passport')->nullable();
            $table->string('pass_number');
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
