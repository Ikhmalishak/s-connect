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
        Schema::table('shipment_transports', function (Blueprint $table) {
            $table->boolean('is_on_hold')->default(false)->after('failed_at');
            $table->text('hold_reason')->nullable()->after('is_on_hold');
            $table->foreignId('hold_by')->nullable()->constrained('users')->onDelete('set null')->after('hold_reason');
            $table->timestamp('hold_at')->nullable()->after('hold_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_transports', function (Blueprint $table) {
            $table->dropForeign(['hold_by']);
            $table->dropColumn(['is_on_hold', 'hold_reason', 'hold_by', 'hold_at']);
        });
    }
};
