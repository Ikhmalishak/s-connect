<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingRequirement extends Model
{
    protected $fillable = [
        'region',
        'destination',
        'risk_level',
        'strength_mm',
        'requires_seals',
        'last_updated_by',
        'attachment_path',
        'change_requested_at',
        'requires_approval',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'requires_seals' => 'boolean',
        'requires_approval' => 'boolean',
        'change_requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ShippingRequirementChange::class);
    }

    public function pendingChanges(): HasMany
    {
        return $this->hasMany(ShippingRequirementChange::class)->where('status', 'pending');
    }
}
