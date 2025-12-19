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
        Schema::create('container_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('skp_site');
            $table->string('container_type');
            $table->string('container_number')->unique();
            $table->date('shipment_date');
            $table->string('country');
            $table->string('forwarder');
            $table->string('hauler');
            $table->string('sku_number');
            $table->string('container_size');
            $table->string('model');
            $table->string('work_order');
            $table->boolean('high_sec')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_shipments');
    }
};
