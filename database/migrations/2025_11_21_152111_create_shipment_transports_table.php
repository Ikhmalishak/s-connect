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
            $table->string('transport_type');          
            $table->string('transport_number');
            $table->string('sku_number');
            $table->string('model_project');
            $table->string('forwarder');
            $table->string('country');
            $table->string('work_order');
            //only for container            
            $table->string('hauler')->nullable();
            $table->string('high_security_seal')->nullable();
            $table->string('gps')->nullable();
            $table->string('fork_seal')->nullable();
            $table->string('temporary_seal')->nullable();
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
