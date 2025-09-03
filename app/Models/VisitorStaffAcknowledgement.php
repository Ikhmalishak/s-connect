<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorStaffAcknowledgement extends Model
{
    protected $fillable = [
        'visitors',
    ];

    protected $casts = [
        'visitors' => 'array', // auto cast to array/json
    ];
}
