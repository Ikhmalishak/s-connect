<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTransportPhoto extends Model
{
    protected $fillable = [
        'shipment_transport_id',
        'label',
        'photo_path',
        'taken_by',
    ];

    public function shipmentTransport()
    {
        return $this->belongsTo(ShipmentTransport::class);
    }
}
