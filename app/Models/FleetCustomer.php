<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetCustomer extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'phone', 'id_number',
        'license_number', 'license_expiry', 'email',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(FleetBooking::class, 'customer_id');
    }
}
