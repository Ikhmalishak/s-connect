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
        Schema::create('archive_container_details', function (Blueprint $table) {
            $table->id();
            $table->string('container_truck_info_rev1')->nullable(); // Foreign key to connect with reports
            $table->string('row_checksum')->nullable();
            $table->datetime('modified_on')->nullable();
            $table->string('skp_site')->nullable();
            $table->string('container_truck')->nullable(); // "Container" or "Truck"
            $table->string('container_truck_number')->nullable();
            $table->date('date')->nullable();
            $table->string('country')->nullable();
            $table->string('forwarder')->nullable();
            $table->string('container_size')->nullable();
            $table->string('hauler')->nullable();
            $table->string('sku_number')->nullable();
            $table->string('model_project')->nullable();
            $table->string('work_order')->nullable();
            $table->boolean('high_security_seal')->nullable();
            $table->string('high_security_seal_sn')->nullable();
            $table->boolean('gps')->nullable();
            $table->string('outside_gps_sn')->nullable();
            $table->string('inside_gps_sn')->nullable();
            $table->string('gps_country')->nullable();
            $table->boolean('fork_seal')->nullable();
            $table->string('fork_seal_size')->nullable();
            $table->string('fork_seal_sn')->nullable();
            $table->boolean('temporary_seal')->nullable();
            $table->string('temporary_seal_sn')->nullable();
            $table->datetime('created_on')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_container_details');
    }
};
