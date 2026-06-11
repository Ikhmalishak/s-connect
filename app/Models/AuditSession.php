<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_type_id',
        'user_id',
        'department_id',
        'site_id',
        'date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'status' => 'string',
    ];

    public function auditType()
    {
        return $this->belongsTo(AuditType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function answers()
    {
        return $this->hasMany(AuditAnswer::class);
    }

    public function findingActions()
    {
        return $this->hasManyThrough(
            AuditFindingAction::class,
            AuditAnswer::class,
            'audit_session_id', // Foreign key on audit_answers
            'audit_answer_id',  // Foreign key on audit_finding_actions
            'id',               // Local key on audit_sessions
            'id'                // Local key on audit_answers
        );
    }
}
