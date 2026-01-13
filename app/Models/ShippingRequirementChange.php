<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRequirementChange extends Model
{
    protected $fillable = [
        'shipping_requirement_id',
        'requested_by',
        'change_type',
        'original_data',
        'proposed_data',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comments',
    ];

    protected $casts = [
        'original_data' => 'array',
        'proposed_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function shippingRequirement(): BelongsTo
    {
        return $this->belongsTo(ShippingRequirement::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}