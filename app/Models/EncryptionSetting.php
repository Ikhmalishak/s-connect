<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncryptionSetting extends Model
{
    protected $fillable = ['table_name', 'label', 'field_name', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];
}

