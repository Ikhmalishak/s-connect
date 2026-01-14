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
        Schema::table('shipping_requirements', function (Blueprint $table) {
            // Rename requires_seals to requires_gps
            $table->renameColumn('requires_seals', 'requires_gps');

            // Add new strength column first
            $table->integer('strength')->nullable()->after('risk_level');
        });

        // Migrate data: extract number from strength_mm (e.g., "8mm" -> 8)
        DB::statement("UPDATE shipping_requirements SET strength = CAST(REPLACE(strength_mm, 'mm', '') AS UNSIGNED) WHERE strength_mm IS NOT NULL");

        Schema::table('shipping_requirements', function (Blueprint $table) {
            // Drop the old strength_mm column
            $table->dropColumn('strength_mm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requirements', function (Blueprint $table) {
            // Add back strength_mm column
            $table->string('strength_mm')->nullable()->after('risk_level');

            // Migrate data back: convert integer to string with 'mm' suffix
            DB::statement("UPDATE shipping_requirements SET strength_mm = CONCAT(strength, 'mm') WHERE strength IS NOT NULL");

            // Drop the new strength column
            $table->dropColumn('strength');

            // Rename requires_gps back to requires_seals
            $table->renameColumn('requires_gps', 'requires_seals');
        });
    }
};
