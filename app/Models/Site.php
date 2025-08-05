<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'name',
        'site_code'
    ];
    public function visitor()
    {
        return $this->hasOne(Visitor::class);
    }

}
