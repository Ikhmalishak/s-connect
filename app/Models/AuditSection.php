<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_type_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function auditType()
    {
        return $this->belongsTo(AuditType::class);
    }

    public function questions()
    {
        return $this->hasMany(AuditQuestion::class, 'audit_section_id')->orderBy('sort_order');
    }
}
