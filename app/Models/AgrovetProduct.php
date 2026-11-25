<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgrovetProduct extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'category', 'unit_base', 'unit_alt',
        'conversion_factor', 'grade', 'reorder_level', 'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'reorder_level' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(AgrovetStockMovement::class, 'product_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(AgrovetPurchase::class, 'product_id');
    }
}
