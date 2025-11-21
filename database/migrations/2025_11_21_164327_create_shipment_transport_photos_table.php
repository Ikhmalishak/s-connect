<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_transport_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_transport_id')->constrained('shipment_transports')->onDelete('cascade');
            $table->enum('stage', ['empty', 'half', 'full', 'sealed', 'inspection', 'dispatch']);
            $table->string('photo_path');
            $table->string('original_name')->nullable();
            $table->string('file_size')->nullable();
            $table->text('description')->nullable();
            $table->string('taken_by');
            $table->timestamps();
            
            $table->index(['shipment_transport_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_transport_photos');
    }
};