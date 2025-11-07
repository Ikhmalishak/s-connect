<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('user_name');
            $table->string('user_id');
            $table->string('email');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->date('date');
            $table->string('purpose')->nullable();
            $table->enum('status',['active','cancelled','completed']);
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_reservations');
    }
};

