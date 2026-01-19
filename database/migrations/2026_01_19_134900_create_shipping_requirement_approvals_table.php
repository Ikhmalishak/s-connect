<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_requirement_approvals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('shipping_requirement_change_id');

            $table->foreign(
                'shipping_requirement_change_id',
                'sr_approvals_change_fk'
            )
                ->references('id')
                ->on('shipping_requirement_changes')
                ->onDelete('cascade');

            $table->string('department');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign(
                'approved_by',
                'sr_approvals_user_fk'
            )
                ->references('id')
                ->on('users')
                ->nullOnDelete();

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
