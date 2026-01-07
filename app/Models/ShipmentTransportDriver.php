<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTransportDriver extends Model
{
    protected $table = 'shipment_transport_drivers';

    protected $fillable = [
        'visitor_id',
        'shipment_transport_id',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function shipmentTransport()
    {
        return $this->belongsTo(ShipmentTransport::class);
    }
}
