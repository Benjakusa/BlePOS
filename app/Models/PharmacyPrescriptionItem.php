<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyPrescriptionItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['prescribed_quantity' => 'decimal:4', 'dispensed_quantity' => 'decimal:4'];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(PharmacyPrescription::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
