<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformApproval extends Model
{
    protected $fillable = ['action_type', 'target_type', 'target_id', 'requested_by', 'approved_by', 'cancelled_by', 'status', 'reason', 'cancellation_reason', 'payload', 'expires_at', 'approved_at', 'cancelled_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'expires_at' => 'datetime', 'approved_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'approved_by');
    }
}
