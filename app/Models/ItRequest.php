<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'request_date',
        'type',
        'description',
        'justification',
        'urgency',
        'attachment',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
