<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvitation extends Model
{
    protected $fillable = ['email', 'platform_role_id', 'invited_by', 'token_hash', 'status', 'expires_at', 'accepted_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PlatformRole::class, 'platform_role_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'invited_by');
    }
}
