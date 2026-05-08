<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_section_id',
        'question_text',
        'is_mandatory',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'input_type' => 'string',
    ];

    public function section()
    {
        return $this->belongsTo(AuditSection::class, 'audit_section_id');
    }

    public function answers()
    {
        return $this->hasMany(AuditAnswer::class);
    }
}
