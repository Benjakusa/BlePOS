<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GymMember extends Model
{
    use BelongsToShop, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'phone', 'email', 'dob', 'photo_path', 'notes',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(GymSubscription::class, 'member_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(GymPayment::class, 'member_id');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(GymCheckin::class, 'member_id');
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(GymClassBooking::class, 'member_id');
    }
}
