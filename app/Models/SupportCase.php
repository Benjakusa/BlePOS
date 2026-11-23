<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportCase extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'sla_due_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<PlatformUser, $this>
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'assigned_agent_id');
    }

    /**
     * @return HasMany<SupportCaseNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(SupportCaseNote::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->sla_due_at && $this->sla_due_at < now();
    }
}
