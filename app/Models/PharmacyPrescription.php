<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyPrescription extends Model
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
            'prescription_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function dispensingLogs(): HasMany
    {
        return $this->hasMany(PharmacyDispensingLog::class, 'prescription_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPrescriptionItem::class, 'prescription_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->lt(now()->startOfDay());
    }
}
