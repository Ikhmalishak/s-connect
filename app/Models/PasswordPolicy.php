<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordPolicy extends Model
{
    protected $fillable = [
        'min_length',
        'require_letters',
        'require_numbers',
        'require_mixed_case',
        'require_symbols',
        'message'
    ];

    protected $casts = [
        'require_letters' => 'boolean',
        'require_numbers' => 'boolean',
        'require_mixed_case' => 'boolean',
        'require_symbols' => 'boolean',
    ];
}