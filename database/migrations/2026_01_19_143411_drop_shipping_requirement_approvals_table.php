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
        Schema::dropIfExists('shipping_requirement_approvals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This migration is irreversible as we cannot recreate the table
        // with the same data that was lost. In a production environment,
        // you would need to restore from backup if rollback is needed.
    }
};
