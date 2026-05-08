<?php

use App\Models\AuditSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AuditSection::class)->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_questions');
    }
};
