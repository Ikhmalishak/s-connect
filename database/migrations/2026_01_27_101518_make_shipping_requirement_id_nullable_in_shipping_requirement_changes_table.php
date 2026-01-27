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
        Schema::table('shipping_requirement_changes', function (Blueprint $table) {
            $table->dropForeign(['shipping_requirement_id']);
            $table->foreignId('shipping_requirement_id')->nullable()->change()->constrained('shipping_requirements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requirement_changes', function (Blueprint $table) {
            $table->dropForeign(['shipping_requirement_id']);
            $table->foreignId('shipping_requirement_id')->constrained('shipping_requirements')->onDelete('cascade');
        });
    }
};
