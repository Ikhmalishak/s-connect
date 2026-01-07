<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    //fillable
    protected $fillable = [
        'gate_pass_id',
        'site_id',
        'visitor_name',
        'vehicle_number',
        'time_register',
        'time_in',
        'time_out',
        'purpose',
        'pic',
        'visitor_company',
        'reasons',
        'ic_number',
        'phone_number',
        'date',
        'remarks',
        'passport',
        'visitor_type',
        'other_reasons',
        'person_to_meet',
    ];

    public function gatePass()
    {
        return $this->belongsTo(GatePass::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function acknowledgements()
    {
        return $this->belongsToMany(
            VisitorStaffAcknowledgement::class,
            'visitor_ack_pivot',
            'visitor_id',
            'visitor_staff_acknowledgement_id'
        )->withTimestamps();
    }

    public function shipmentTransportDrivers()
    {
        return $this->hasMany(ShipmentTransportDriver::class);
    }

    public function shipmentTransport()
    {
        return $this->hasOneThrough(
            ShipmentTransport::class,
            ShipmentTransportDriver::class,
            'visitor_id',           // Foreign key on shipment_transport_drivers table
            'id',                   // Foreign key on shipment_transports table
            'id',                   // Local key on visitors table
            'shipment_transport_id' // Local key on shipment_transport_drivers table
        )->select('shipment_transports.*'); // Specify table to avoid ambiguous column
    }

    protected $casts = [
        'ic_number' => 'encrypted',
        'phone_number' => 'encrypted',
        'passport' => 'encrypted',
    ];

}
