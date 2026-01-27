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
        Schema::table('shipping_requirements', function (Blueprint $table) {
            $table->boolean('requires_fork_seal')->default(true)->after('strength');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requirements', function (Blueprint $table) {
            $table->dropColumn('requires_fork_seal');
        });
    }
};
