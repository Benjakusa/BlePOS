<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSecurityKey extends Model
{
    protected $fillable = [
        'key_id',
        'name',
        'key_type',
        'algorithm',
        'version',
        'status',
        'fingerprint',
        'envelope_wrapped_by_key_id',
        'rotation_schedule_days',
        'last_rotated_at',
        'next_rotation_due_at',
        'reencryption_progress_percent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'rotation_schedule_days' => 'integer',
            'reencryption_progress_percent' => 'integer',
            'last_rotated_at' => 'datetime',
            'next_rotation_due_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
