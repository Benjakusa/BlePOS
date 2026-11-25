<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarageVehicle extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'customer_id', 'plate', 'vin',
        'make', 'model', 'year', 'color', 'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(GarageCustomer::class, 'customer_id');
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(GarageJobCard::class, 'vehicle_id');
    }
}
