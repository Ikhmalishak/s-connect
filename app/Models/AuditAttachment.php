<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_answer_id',
        'file_path',
    ];

    public function answer()
    {
        return $this->belongsTo(AuditAnswer::class);
    }
}
