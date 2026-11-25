<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetBooking extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'vehicle_id', 'customer_id', 'driver_id',
        'pickup_at', 'return_at', 'actual_return_at', 'status', 'rate_type',
        'agreed_rate', 'deposit_amount', 'deposit_paid', 'deposit_refunded',
        'total_billed', 'late_surcharge', 'damage_charge', 'fuel_charge', 'notes',
    ];

    protected $casts = [
        'pickup_at' => 'datetime',
        'return_at' => 'datetime',
        'actual_return_at' => 'datetime',
        'agreed_rate' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'deposit_refunded' => 'boolean',
        'total_billed' => 'decimal:2',
        'late_surcharge' => 'decimal:2',
        'damage_charge' => 'decimal:2',
        'fuel_charge' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(FleetVehicle::class, 'vehicle_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(FleetCustomer::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(FleetDriver::class, 'driver_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FleetPayment::class, 'booking_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(FleetInspection::class, 'booking_id');
    }
}
