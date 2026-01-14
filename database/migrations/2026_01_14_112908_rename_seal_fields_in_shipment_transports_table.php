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
        Schema::table('shipment_transports', function (Blueprint $table) {
            // Rename existing fields to add _sn suffix
            $table->renameColumn('high_security_seal', 'high_security_seal_sn');
            $table->renameColumn('fork_seal', 'fork_seal_sn');
            $table->renameColumn('temporary_seal', 'temporary_seal_sn');

            // Add new fields
            $table->string('inside_gps_sn')->nullable()->after('high_security_seal_sn');
            $table->string('outside_gps_sn')->nullable()->after('inside_gps_sn');
            $table->string('fork_seal_size')->nullable()->after('fork_seal_sn');
        });

        // Migrate existing GPS data to inside_gps_sn
        DB::statement('UPDATE shipment_transports SET inside_gps_sn = gps WHERE gps IS NOT NULL');

        Schema::table('shipment_transports', function (Blueprint $table) {
            // Drop the old gps column
            $table->dropColumn('gps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_transports', function (Blueprint $table) {
            // Add back the gps column
            $table->string('gps')->nullable()->after('high_security_seal_sn');

            // Migrate data back
            DB::statement('UPDATE shipment_transports SET gps = inside_gps_sn WHERE inside_gps_sn IS NOT NULL');
        });

        Schema::table('shipment_transports', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['inside_gps_sn', 'outside_gps_sn', 'fork_seal_size']);

            // Rename back to original names
            $table->renameColumn('high_security_seal_sn', 'high_security_seal');
            $table->renameColumn('fork_seal_sn', 'fork_seal');
            $table->renameColumn('temporary_seal_sn', 'temporary_seal');
        });
    }
};
