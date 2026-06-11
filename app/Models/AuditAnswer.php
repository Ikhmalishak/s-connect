<?php

namespace App\Models;

use App\Enums\AuditAnswerEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_session_id',
        'audit_question_id',
        'answer',
        'photo_path',
        'remarks',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'answer' => AuditAnswerEnum::class,
    ];

    public function session()
    {
        return $this->belongsTo(AuditSession::class);
    }

    public function question()
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }

    public function attachments()
    {
        return $this->hasMany(AuditAttachment::class);
    }

    public function findingAction()
    {
        return $this->hasOne(AuditFindingAction::class, 'audit_answer_id');
    }
}
