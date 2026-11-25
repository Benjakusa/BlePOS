<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetVehicle extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'plate', 'make', 'model', 'year', 'color',
        'type', 'daily_rate', 'weekly_rate', 'deposit_amount',
        'insurance_expiry', 'inspection_expiry', 'license_expiry',
        'status', 'mileage',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'insurance_expiry' => 'date',
        'inspection_expiry' => 'date',
        'license_expiry' => 'date',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(FleetBooking::class, 'vehicle_id');
    }

    public function maintenance(): HasMany
    {
        return $this->hasMany(FleetMaintenance::class, 'vehicle_id');
    }
}
