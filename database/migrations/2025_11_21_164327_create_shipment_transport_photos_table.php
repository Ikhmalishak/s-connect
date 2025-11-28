<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_transport_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_transport_id')->constrained('shipment_transports')->onDelete('cascade');
            $table->enum('label', [
                "pallet_condition_photo",
                "pallet_label_photo",
                "gps_photo_before_installation",
                "container_truck_photo",
                "empty_container_photo",
                "inside_gps_photo",
                "half_loaded_photo",
                "one_side_door_closed_with_container_number_photo",
                "complete_loaded_photo",
                "outside_gps_photo",
                "security_seal_photo",
                "container_full_seal_photo"
            ]);
            $table->string('photo_path');
            $table->string('taken_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_transport_photos');
    }
};