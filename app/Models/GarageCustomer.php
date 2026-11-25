<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarageCustomer extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id', 'shop_id', 'name', 'phone', 'email'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(GarageVehicle::class, 'customer_id');
    }

    public function jobCards(): HasMany
    {
        return $this->hasMany(GarageJobCard::class, 'customer_id');
    }
}
