<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymCheckin extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shop_id', 'member_id', 'checked_in_at', 'method',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(GymMember::class, 'member_id');
    }
}
