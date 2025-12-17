<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTransport extends Model
{
    protected $fillable = [
        'site_id',
        'transport_type',
        'transport_number',
        'sku_number',
        'model_project',
        'forwarder',
        'hauler',
        'high_security_seal',
        'gps',
        'fork_seal',
        'temporary_seal',
        'country',
        'work_order',
        'date',
        'status',
    ];

public function inspection()
{
    return $this->hasOne(ShipmentTransportInspection::class);
}

public function photo()
{
    return $this->hasMany(ShipmentTransportPhoto::class);
}
}
