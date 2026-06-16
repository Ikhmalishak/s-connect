<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerGps extends Model
{
    protected $fillable = [
        'overhaul_id',
        'reject_reason',
        'remark',
        'date',
    ];
}
