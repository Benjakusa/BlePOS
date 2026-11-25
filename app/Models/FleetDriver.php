<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetDriver extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'phone',
        'license_number', 'license_expiry', 'is_active',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(FleetBooking::class, 'driver_id');
    }
}
