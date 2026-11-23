<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSession extends Model
{
    protected $fillable = [
        'platform_user_id', 'session_hash', 'session_identifier', 'ip_address', 'user_agent',
        'logged_in_at', 'last_activity_at', 'expires_at', 'revoked_at',
    ];

    protected $hidden = ['session_identifier'];

    protected function casts(): array
    {
        return [
            'session_identifier' => 'encrypted',
            'logged_in_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'platform_user_id');
    }
}
