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
        Schema::create('shipment_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->string('transport_type')->nullable();
            $table->string('sku_number')->nullable();
            $table->string('model_project')->nullable();
            $table->string('forwarder')->nullable();
            $table->string('country')->nullable();
            $table->string('work_order')->nullable();
            //only for container            
            $table->string('hauler');
            $table->string('high_security_seal');
            $table->string('gps');
            $table->string('fork_seal');
            $table->string('temporary_seal');
            $table->date('date')->nullable();
            $table->enum('status', ['pending', 'in_transit', 'delivered'])->default('pending');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_transports');
    }
};
