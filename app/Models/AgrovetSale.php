<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgrovetSale extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'customer_id', 'sale_number', 'total_amount',
        'payment_type', 'reference', 'payment_meta', 'sold_at', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_meta' => 'array',
        'sold_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(AgrovetCustomer::class, 'customer_id');
    }
}
