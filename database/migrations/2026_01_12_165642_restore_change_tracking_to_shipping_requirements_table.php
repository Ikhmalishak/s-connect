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
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('attachment_path')->nullable();
            $table->timestamp('change_requested_at')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requirements', function (Blueprint $table) {
            $table->dropForeign(['last_updated_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'last_updated_by',
                'attachment_path',
                'change_requested_at',
                'requires_approval',
                'approved_by',
                'approved_at'
            ]);
        });
    }
};
