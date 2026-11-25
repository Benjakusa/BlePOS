<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaragePayment extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'job_id', 'amount',
        'method', 'reference', 'payment_meta', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(GarageJobCard::class, 'job_id');
    }
}
