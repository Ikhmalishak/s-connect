<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ShipmentTransport extends Model
{
    protected $fillable = [
        'site_id',
        'transport_type',
        'size',
        'transport_number',
        'sku_number',
        'model_project',
        'forwarder',
        'hauler',
        'driver_name',
        'driver_id',
        'high_security_seal_sn',
        'inside_gps_sn',
        'outside_gps_sn',
        'fork_seal_sn',
        'fork_seal_size',
        'temporary_seal_sn',
        'country',
        'work_order',
        'date',
        'status',
        'stage',
        'failed_at',
        'created_by',
        'is_on_hold',
        'hold_reason',
        'hold_by',
        'hold_at',
    ];

    protected $casts = [
        'is_on_hold' => 'boolean',
        'hold_at' => 'datetime',
    ];

    public function inspection()
{
    return $this->hasOne(ShipmentTransportInspection::class);
}

public function photo()
{
    return $this->hasMany(ShipmentTransportPhoto::class);
}

public function approvals()
{
    return $this->hasMany(ShipmentTransportApproval::class);
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function holdBy()
{
    return $this->belongsTo(User::class, 'hold_by');
}

public function site()
{
    return $this->belongsTo(\App\Models\Site::class);
}

public function shipmentTransportDrivers()
{
    return $this->hasMany(ShipmentTransportDriver::class);
}
}
