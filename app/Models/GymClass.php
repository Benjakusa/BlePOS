<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymClass extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'trainer_id', 'name',
        'scheduled_at', 'duration_minutes', 'capacity', 'is_cancelled',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'capacity' => 'integer',
        'is_cancelled' => 'boolean',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(GymTrainer::class, 'trainer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(GymClassBooking::class, 'class_id');
    }
}
