<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSecurityIncidentNote extends Model
{
    protected $fillable = [
        'incident_id',
        'platform_user_id',
        'note',
        'is_containment_action',
    ];

    protected function casts(): array
    {
        return [
            'is_containment_action' => 'boolean',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(PlatformSecurityIncident::class, 'incident_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
