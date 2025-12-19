<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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
        'stage',
        'failed_at',
        'created_by',
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

public function site()
{
    return $this->belongsTo(\App\Models\Site::class);
}
}
