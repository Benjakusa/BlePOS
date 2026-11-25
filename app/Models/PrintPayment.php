<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintPayment extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'job_id', 'tenant_id', 'shop_id', 'type',
        'amount', 'method', 'reference', 'payment_meta', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class, 'job_id');
    }
}
