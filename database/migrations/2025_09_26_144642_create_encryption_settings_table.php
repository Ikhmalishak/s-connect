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
        Schema::create('encryption_settings', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');   // "visitors"
            $table->string('field_name');   // "ic_number"
            $table->string('label');        // "Visitor IC Number"
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encryption_settings');
    }
};
