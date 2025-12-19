<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerShipment extends Model
{
    protected $fillable = [
        'skp_site',
        'container_type',
        'container_number',
        'shipment_date',
        'country',
        'forwarder',
        'hauler',
        'sku_number',
        'container_size',
        'model',
        'work_order',
        'high_sec',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'high_sec' => 'boolean',
    ];
}
