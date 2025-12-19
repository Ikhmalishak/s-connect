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
        Schema::table('shipment_transport_approvals', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique('st_approvals_transport_dept_unique');

            // Create new unique constraint including approval_type
            $table->unique(['shipment_transport_id', 'department', 'approval_type'], 'st_approvals_transport_dept_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_transport_approvals', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('st_approvals_transport_dept_type_unique');

            // Recreate the original unique constraint
            $table->unique(['shipment_transport_id', 'department'], 'st_approvals_transport_dept_unique');
        });
    }
};
