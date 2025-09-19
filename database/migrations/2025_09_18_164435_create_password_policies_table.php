<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_length')->default(8);
            $table->boolean('require_letters')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_mixed_case')->default(false);
            $table->boolean('require_symbols')->default(false);
            $table->string('message')->nullable();
            $table->timestamps();
        });

        // Insert default row
        DB::table('password_policies')->insert([
            'min_length' => 12,
            'require_letters' => true,
            'require_numbers' => true,
            'require_mixed_case' => true,
            'require_symbols' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('password_policies');
    }
};

