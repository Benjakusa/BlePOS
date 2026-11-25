<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymPayment extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'subscription_id', 'member_id',
        'amount', 'method', 'reference', 'payment_meta', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(GymSubscription::class, 'subscription_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GymMember::class, 'member_id');
    }
}
