<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old table and recreate with per-answer relationship
        Schema::dropIfExists('audit_finding_actions');

        Schema::create('audit_finding_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_answer_id')->constrained('audit_answers')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('corrective_evidence')->nullable();
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_finding_actions');

        Schema::create('audit_finding_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_session_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('corrective_evidence')->nullable();
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }
};