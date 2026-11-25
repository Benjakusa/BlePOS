<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarageJobItem extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'job_id', 'type', 'description',
        'part_id', 'quantity', 'unit_price', 'total', 'hours',
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
