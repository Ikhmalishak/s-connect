<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorCompany extends Model
{
    //fillable
    protected $fillable = ['name'];

    public function visitorForms()
    {
        return $this->hasMany(Visitor::class);
    }
}
