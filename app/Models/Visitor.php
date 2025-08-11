<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    //fillable
    protected $fillable = [
        'gate_pass_id',        
        'site_id',
        'visitor_name',
        'vehicle_number',
        'time_register',
        'time_in',
        'time_out',
        'purpose',
        'pic',
        'visitor_company',
        'reasons',
        'ic_number',
        'phone_number',
        'date',
        'remarks',
        'passport',
        'visitor_type',
        'other_reasons'
    ];

    public function gatePass()
    {
        return $this->belongsTo(GatePass::class);
    }

    public function sites()
    {
        return $this->belongsTo(Site::class);
    }

}

