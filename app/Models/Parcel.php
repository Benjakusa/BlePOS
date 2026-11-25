<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcel extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'fee' => 'decimal:2',
            'received_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'branch_received_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function originOffice(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'origin_shop_id');
    }

    public function currentOffice(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'current_shop_id');
    }

    public function destinationOffice(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'destination_shop_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function branchReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_received_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ParcelEvent::class)->orderBy('occurred_at')->orderBy('id');
    }
}
