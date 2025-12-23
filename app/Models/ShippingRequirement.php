<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRequirement extends Model
{
    protected $fillable = [
        'region',
        'destination',
        'risk_level',
        'strength_mm',
        'requires_seals',
    ];

    protected $casts = [
        'requires_seals' => 'boolean',
    ];
}
