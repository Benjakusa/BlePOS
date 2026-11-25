<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintMaterialConsumption extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $table = 'print_material_consumption';

    protected $fillable = [
        'job_id', 'material_id', 'tenant_id', 'shop_id',
        'quantity_used', 'consumed_at',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'consumed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class, 'job_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(PrintMaterial::class, 'material_id');
    }
}
