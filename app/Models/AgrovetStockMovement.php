<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgrovetStockMovement extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'product_id', 'batch_number', 'expiry_date',
        'movement_type', 'quantity', 'unit', 'unit_price',
        'reference_id', 'reference_type', 'moved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'expiry_date' => 'date',
        'moved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(AgrovetProduct::class, 'product_id');
    }
}
