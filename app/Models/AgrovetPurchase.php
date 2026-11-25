<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgrovetPurchase extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'supplier_id', 'product_id', 'batch_number',
        'expiry_date', 'quantity', 'unit_cost', 'total_cost', 'payment_status', 'purchased_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'purchased_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(AgrovetSupplier::class, 'supplier_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AgrovetProduct::class, 'product_id');
    }
}
