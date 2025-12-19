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
        Schema::table('shipment_transport_approvals', function (Blueprint $table) {
            // Add approval_type field with default 'loading'
            $table->enum('approval_type', ['inspection', 'loading'])->default('loading')->after('department');
        });

        // Update existing quality approvals to be inspection type
        DB::table('shipment_transport_approvals')
            ->where('department', 'quality')
            ->update(['approval_type' => 'inspection']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_transport_approvals', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('st_approvals_transport_dept_type_unique');

            // Drop the approval_type column
            $table->dropColumn('approval_type');

            // Recreate the original unique constraint
            $table->unique(['shipment_transport_id', 'department'], 'st_approvals_transport_dept_unique');
        });
    }
};
