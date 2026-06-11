<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditFindingAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_answer_id',
        'description',
        'corrective_evidence',
        'status',
        'submitted_by',
        'reviewed_by',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function answer()
    {
        return $this->belongsTo(AuditAnswer::class, 'audit_answer_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}