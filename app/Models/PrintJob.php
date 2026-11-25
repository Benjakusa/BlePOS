<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintJob extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'customer_name', 'customer_phone', 'title',
        'status', 'deposit_amount', 'deposit_paid_at', 'total_amount',
        'balance_due', 'notes', 'delivered_at',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'deposit_paid_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PrintJobItem::class, 'job_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(PrintQuote::class, 'job_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PrintPayment::class, 'job_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PrintRevision::class, 'job_id');
    }

    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(PrintMaterialConsumption::class, 'job_id');
    }
}
