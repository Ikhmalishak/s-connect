<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionQuestion extends Model
{
    protected $fillable = [
        'question',
    ];
    
    public function answers()
    {
        return $this->hasMany(InspectionAnswer::class);
    }
}
