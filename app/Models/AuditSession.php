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

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function answers()
    {
        return $this->hasMany(AuditAnswer::class);
    }
}
