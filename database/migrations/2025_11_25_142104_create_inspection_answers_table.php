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
        Schema::create('inspection_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_transport_inspection_id')
                ->constrained('shipment_transport_inspections')
                ->onDelete('cascade');

            $table->foreignId('inspection_question_id')
                ->constrained('inspection_questions')
                ->onDelete('cascade');

            $table->boolean('passed')->nullable();     // pass/fail
            $table->string('photo_path')->nullable();  // if fail -> require photo
            $table->string('remarks')->nullable();     // optional note
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_answers');
    }
};
