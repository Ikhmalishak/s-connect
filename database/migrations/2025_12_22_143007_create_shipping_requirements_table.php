<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('destination');
            $table->string('risk_level');
            $table->string('strength_mm');
            $table->boolean('requires_seals')->default(false);
            $table->timestamps();

            $table->unique(['region', 'destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_requirements');
    }
};
