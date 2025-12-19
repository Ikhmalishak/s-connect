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
        // Modify the enum to include missing stages
        DB::statement("ALTER TABLE shipment_transports MODIFY COLUMN stage ENUM('container_checking', 'container_checking_approval', 'container_loading_report', 'container_loading_report_approval', 'onboarding_ready', 'security_checking', 'onboarded') DEFAULT 'container_checking'");
        DB::statement("ALTER TABLE shipment_transports MODIFY COLUMN failed_at ENUM('container_checking', 'container_checking_approval', 'container_loading_report', 'container_loading_report_approval', 'onboarding_ready', 'security_checking', 'onboarded') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE shipment_transports MODIFY COLUMN stage ENUM('container_checking', 'container_checking_approval', 'container_loading_report', 'container_loading_report_approval', 'security_checking') DEFAULT 'container_checking'");
        DB::statement("ALTER TABLE shipment_transports MODIFY COLUMN failed_at ENUM('container_checking', 'container_checking_approval', 'container_loading_report', 'container_loading_report_approval', 'security_checking') NULL");
    }
};
