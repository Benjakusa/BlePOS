<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GymSubscription extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'member_id', 'plan_id', 'status',
        'start_date', 'end_date', 'freeze_start', 'freeze_end',
        'session_credits_remaining', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'freeze_start' => 'date',
        'freeze_end' => 'date',
        'session_credits_remaining' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(GymMember::class, 'member_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(GymPlan::class, 'plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GymPayment::class, 'subscription_id');
    }
}
