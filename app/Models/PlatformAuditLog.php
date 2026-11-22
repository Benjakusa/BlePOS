<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAuditLog extends Model
{
    protected $table = 'platform_audit_log';

    public $timestamps = false;

    protected $guarded = ['id', 'actor_id', 'action', 'target_type', 'target_id', 'details', 'previous_hash', 'entry_hash', 'occurred_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'occurred_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'actor_id');
    }
}
