<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformDataProtectionRequest extends Model
{
    protected $fillable = [
        'request_number',
        'tenant_id',
        'subject_name',
        'subject_email',
        'subject_phone',
        'request_type',
        'status',
        'legal_basis',
        'due_date',
        'rejection_reason',
        'resolution_summary',
        'assigned_officer_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assigned_officer_id');
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['completed', 'rejected']) && $this->due_date->isPast();
    }
}
