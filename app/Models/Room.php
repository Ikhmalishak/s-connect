<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'capacity',
        'location',
        'status',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function reservations()
    {
        return $this->hasMany(RoomReservation::class);
    }
    
    public function activeReservations()
    {
        return $this->hasMany(RoomReservation::class)
            ->whereNotIn('status', ['cancelled', 'rejected', 'completed']);
    }
}
