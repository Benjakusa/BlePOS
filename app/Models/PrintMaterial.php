<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintMaterial extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'unit',
        'quantity_on_hand', 'reorder_level', 'cost_per_unit',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
    ];

    public function consumptions(): HasMany
    {
        return $this->hasMany(PrintMaterialConsumption::class, 'material_id');
    }
}
