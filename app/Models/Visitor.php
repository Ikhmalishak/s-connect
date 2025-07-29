<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    //fillable
    protected $fillable = [
        'gate_pass_id',
        'visitor_name',
        'vehicle_number',
        'time_register',
        'time_in',
        'time_out',
        'site',
        'purpose',
        'pic',
        'visitor_company',
        'reasons',
        'ic_number',
        'phone_number',
        'date',
        'remarks',
        'passport',
        'visitor_type'
    ];

    public function gatePass()
    {
        return $this->belongsTo(GatePass::class);
    }

}

