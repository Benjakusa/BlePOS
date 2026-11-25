<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarageJobCard extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'vehicle_id', 'customer_id', 'job_number',
        'status', 'estimate_id', 'mileage_in', 'mileage_out', 'notes',
        'received_at', 'delivered_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(GarageVehicle::class, 'vehicle_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(GarageCustomer::class, 'customer_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(GarageEstimate::class, 'estimate_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GarageJobItem::class, 'job_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GaragePayment::class, 'job_id');
    }

    public function partConsumptions(): HasMany
    {
        return $this->hasMany(GaragePartConsumption::class, 'job_id');
    }
}
