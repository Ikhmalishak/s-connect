<?php

use App\Models\AuditType;
use App\Models\Department;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AuditType::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained('users');
            $table->foreignIdFor(Department::class)->constrained('departments');
            $table->foreignIdFor(Site::class)->constrained('sites');
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->text('remarks')->nullable();            
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_sessions');
    }
};
