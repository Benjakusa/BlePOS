<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetMaintenance extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $table = 'fleet_maintenance';

    protected $fillable = [
        'vehicle_id', 'tenant_id', 'shop_id', 'description',
        'started_at', 'completed_at', 'cost', 'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(FleetVehicle::class, 'vehicle_id');
    }
}
