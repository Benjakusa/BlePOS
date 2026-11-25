<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintQuote extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'job_id', 'tenant_id', 'shop_id', 'version',
        'subtotal', 'total', 'is_accepted', 'accepted_at', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'is_accepted' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class, 'job_id');
    }
}
