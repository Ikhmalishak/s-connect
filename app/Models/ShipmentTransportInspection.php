<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTransportInspection extends Model
{

    protected $fillable = [
        'shipment_transport_id',
        'status',
        'received_at',
        'inspected_at',
        'inspected_by',
        'remarks',
    ];

    public function transport()
    {
        return $this->belongsTo(ShipmentTransport::class, 'shipment_transport_id');
    }

    public function answers()
    {
        return $this->hasMany(InspectionAnswer::class);
    }
}
