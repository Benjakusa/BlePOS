<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintRevision extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'job_id', 'tenant_id', 'shop_id', 'revision_number',
        'notes', 'requested_at', 'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class, 'job_id');
    }
}
