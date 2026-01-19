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
        Schema::create('shipping_requirement_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_requirement_change_id')->constrained('shipping_requirement_changes', 'sr_approvals_change_fk')->onDelete('cascade');
            $table->string('department');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'sr_approvals_user_fk')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_requirement_approvals');
    }
};
