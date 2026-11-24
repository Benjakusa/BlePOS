<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformSecurityIncident extends Model
{
    protected $fillable = [
        'incident_number',
        'title',
        'category',
        'severity',
        'status',
        'platform_user_id',
        'reporter_id',
        'tenant_id',
        'description',
        'evidence_metadata',
        'containment_steps',
        'resolution_notes',
        'acknowledged_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_metadata' => 'array',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'reporter_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PlatformSecurityIncidentNote::class, 'incident_id')->orderBy('created_at');
    }
}
