<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_transport_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_transport_id')->constrained('shipment_transports')->onDelete('cascade');
            $table->enum('department', ['shipping', 'quality', 'warehouse', 'security']);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('approved_by')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['shipment_transport_id', 'department'],
                'st_approvals_transport_dept_unique'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_transport_approvals');
    }
};