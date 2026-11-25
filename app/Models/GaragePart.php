<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GaragePart extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'sku', 'quantity_on_hand',
        'reorder_level', 'cost_price', 'selling_price',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function consumptions(): HasMany
    {
        return $this->hasMany(GaragePartConsumption::class, 'part_id');
    }
}
