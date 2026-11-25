<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetInspection extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'booking_id', 'tenant_id', 'shop_id', 'type', 'fuel_level',
        'odometer', 'damage_notes', 'photo_paths', 'inspected_at', 'inspected_by',
    ];

    protected $casts = [
        'photo_paths' => 'array',
        'inspected_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(FleetBooking::class, 'booking_id');
    }
}
