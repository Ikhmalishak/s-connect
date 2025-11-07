<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomReservation extends Model
{
    protected $fillable = [
        'user_name',
        'user_id',
        'email',
        'date',
        'room_id',
        'start_time',
        'end_time',
        'purpose',
        'status',
        'reminder_sent'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
