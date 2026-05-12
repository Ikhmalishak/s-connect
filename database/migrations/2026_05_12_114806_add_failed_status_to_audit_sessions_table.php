<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'failed'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_sessions', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft')->change();
        });
    }
};