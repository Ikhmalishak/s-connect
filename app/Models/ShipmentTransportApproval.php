<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTransportApproval extends Model
{
    protected $fillable = [
        'shipment_transport_id',
        'department',
        'approval_type',
        'approval_status',
        'approved_by',
        'remarks',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function shipmentTransport()
    {
        return $this->belongsTo(ShipmentTransport::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
