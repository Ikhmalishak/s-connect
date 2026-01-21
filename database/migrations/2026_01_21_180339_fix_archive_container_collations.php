<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change collation for archive_container_details table
        DB::statement('ALTER TABLE archive_container_details CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Change collation for archive_container_reports table
        DB::statement('ALTER TABLE archive_container_reports CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to utf8mb4_general_ci if needed
        DB::statement('ALTER TABLE archive_container_details CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        DB::statement('ALTER TABLE archive_container_reports CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }
};
