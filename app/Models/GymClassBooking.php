<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymClassBooking extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'class_id', 'member_id', 'status', 'attended_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GymMember::class, 'member_id');
    }
}
