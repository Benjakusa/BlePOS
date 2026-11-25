<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiningTable extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'seating_capacity' => 'integer',
            'guest_count' => 'integer',
            'occupied_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function currentSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'current_sale_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    public function markOccupied(?int $guestCount = null, ?Sale $sale = null): void
    {
        $this->update([
            'status' => 'occupied',
            'guest_count' => $guestCount ?? $this->guest_count,
            'current_sale_id' => $sale?->id ?? $this->current_sale_id,
            'occupied_at' => now(),
        ]);
    }

    public function markAvailable(): void
    {
        $this->update([
            'status' => 'available',
            'guest_count' => null,
            'current_sale_id' => null,
            'occupied_at' => null,
        ]);
    }
}
