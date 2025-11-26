<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionAnswer extends Model
{
    protected $fillable = [
        'shipment_transport_inspection_id',
        'inspection_question_id',
        'passed',
        'photo_path',
        'remarks',
    ];

    public function inspection()
    {
        return $this->belongsTo(ShipmentTransportInspection::class);
    }

    public function question()
    {
        return $this->belongsTo(InspectionQuestion::class);
    }
}
