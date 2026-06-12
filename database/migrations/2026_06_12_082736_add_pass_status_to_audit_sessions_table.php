<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE audit_sessions MODIFY COLUMN status ENUM('draft', 'submitted', 'approved', 'failed', 'corrective_action_submitted', 'finding_closed', 'pass') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE audit_sessions MODIFY COLUMN status ENUM('draft', 'submitted', 'approved', 'failed', 'corrective_action_submitted', 'finding_closed') DEFAULT 'draft'");
    }
};