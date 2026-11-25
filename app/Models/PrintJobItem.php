<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJobItem extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'job_id', 'tenant_id', 'shop_id', 'description',
        'quantity', 'unit_price', 'total', 'is_labour',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'is_labour' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class, 'job_id');
    }
}
