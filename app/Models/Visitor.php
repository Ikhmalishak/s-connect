<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    //fillable
    protected $fillable = [
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
        'pass_number',
        'phone_number',
        'date',
        'remarks',
        'passport', 
        'visitor_type'
    ];
}
