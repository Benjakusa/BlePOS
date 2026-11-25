<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaragePartConsumption extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $table = 'garage_part_consumption';

    protected $fillable = [
        'tenant_id', 'shop_id', 'job_id', 'part_id',
        'quantity_used', 'consumed_at',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'consumed_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(GarageJobCard::class, 'job_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(GaragePart::class, 'part_id');
    }
}
