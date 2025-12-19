<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include security_checking_photo
        DB::statement("ALTER TABLE shipment_transport_photos MODIFY COLUMN label ENUM(
            'pallet_condition_photo',
            'pallet_label_photo',
            'gps_photo_before_installation',
            'container_truck_photo',
            'empty_container_photo',
            'inside_gps_photo',
            'half_loaded_photo',
            'one_side_door_closed_with_container_number_photo',
            'complete_loaded_photo',
            'outside_gps_photo',
            'security_seal_photo',
            'container_full_seal_photo',
            'security_checking_photo'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove security_checking_photo from enum
        DB::statement("ALTER TABLE shipment_transport_photos MODIFY COLUMN label ENUM(
            'pallet_condition_photo',
            'pallet_label_photo',
            'gps_photo_before_installation',
            'container_truck_photo',
            'empty_container_photo',
            'inside_gps_photo',
            'half_loaded_photo',
            'one_side_door_closed_with_container_number_photo',
            'complete_loaded_photo',
            'outside_gps_photo',
            'security_seal_photo',
            'container_full_seal_photo'
        )");
    }
};
