<?php

namespace App\Models;

use Carbon\Carbon;
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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $year = Carbon::now()->format('Y');

            // get latest record for this year
            $latest = VisitorStaffAcknowledgement::whereYear('created_at', $year)
                ->latest('id')
                ->first();

            // extract running number part
            $nextNumber = 1;
            if ($latest && $latest->ack_number) {
                $lastNumber = intval(substr($latest->ack_number, 8)); // after "SKPYYYY_"
                $nextNumber = $lastNumber + 1;
            }

            // generate code like SKP2025_000001
            $model->ack_number = 'SKP' . $year . '_' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
