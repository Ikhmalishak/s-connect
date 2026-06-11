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

    protected $appends = ['image_url'];

    public function shipmentTransport()
    {
        return $this->belongsTo(ShipmentTransport::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->shipmentTransport && $this->shipmentTransport->is_archived) {
            return url('/file/' . $this->photo_path);
        }

        return asset('storage/' . $this->photo_path);
    }
}
