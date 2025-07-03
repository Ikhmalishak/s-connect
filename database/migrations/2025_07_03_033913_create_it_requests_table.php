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
        Schema::create('it_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('request_date');
            $table->enum('type', ['laptop', 'desktop', 'others']);
            $table->text('description');
            $table->enum('justification', ['new staff', 'replacement', 'others']);
            $table->enum('urgency', ['low', 'medium', 'high'])->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', [
                'pending_hod_approval',
                'rejected_by_hod',
                'pending_it_hod_approval',
                'rejected_by_it_hod',
                'approved',
                'in_progress',
                'completed'
            ])->default('pending_hod_approval');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_requests');
    }
};
