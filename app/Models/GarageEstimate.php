<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GarageEstimate extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'job_id', 'total_parts', 'total_labour',
        'grand_total', 'is_approved', 'approved_at', 'approved_by', 'notes',
    ];

    protected $casts = [
        'total_parts' => 'decimal:2',
        'total_labour' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(GarageJobCard::class, 'job_id');
    }
}
