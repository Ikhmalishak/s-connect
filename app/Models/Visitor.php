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
        'visitor_company_id',
        'reasons',
        'ic_number',
        'pass_number',
        'phone_number',
    ];

    public function visitorCompany()
    {
        return $this->belongsTo(VisitorCompany::class);
    }
}
