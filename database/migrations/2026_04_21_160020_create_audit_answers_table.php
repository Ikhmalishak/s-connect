<?php

use App\Models\AuditQuestion;
use App\Models\AuditSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AuditSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AuditQuestion::class)->constrained('audit_questions')->cascadeOnDelete();
            $table->tinyInteger('answer')->nullable();
            $table->string('photo_path')->nullable();  // if fail -> require photo
            $table->string('remarks')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['audit_session_id', 'audit_question_id']); // prevent duplicate answers
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_answers');
    }
};
