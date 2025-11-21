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
        Schema::create('shipment_transport_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_transport_id')->constrained('shipment_transports')->onDelete('cascade');
            $table->enum('status', ['passed', 'failed'])->default('passed');
            $table->string('remarks'); //if failed, add remarks
            $table->string('inspected_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_transport_inspections');
    }
};
