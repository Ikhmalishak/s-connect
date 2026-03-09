<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GatePass extends Model
{
    protected $fillable = ['pass_number', 'pass_type','site_id','state'];

    public function visitor()
    {
        return $this->hasOne(Visitor::class);
    }
}
