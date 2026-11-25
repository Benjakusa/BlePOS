<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyBatch extends Model
{
    use BelongsToShop, BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
            'initial_quantity' => 'decimal:4',
            'current_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'is_quarantined' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function dispensingLogs(): HasMany
    {
        return $this->hasMany(PharmacyDispensingLog::class, 'batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->lt(now()->startOfDay());
    }

    public function daysUntilExpiry(): int
    {
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function isNearExpiry(int $days = 90): bool
    {
        $diff = $this->daysUntilExpiry();

        return $diff >= 0 && $diff <= $days;
    }
}
