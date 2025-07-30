<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorAcknowledgement extends Model
{
    protected $fillable = [
        'id_type',
        'id_number',
        'acknowledged_at'
    ];

}
