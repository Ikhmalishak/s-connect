<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorStaffAcknowledgement extends Model
{
    protected $fillable = [
        'visitors',
    ];

    protected $casts = [
        'visitors' => 'array', // auto cast to array/json
    ];

    public function visitors()
    {
        return $this->belongsToMany(
            Visitor::class,
            'visitor_ack_pivot',
            'visitor_staff_acknowledgement_id', // pivot FK for this model
            'visitor_id'                        // pivot FK for the related model
        )->withTimestamps();
    }
}
