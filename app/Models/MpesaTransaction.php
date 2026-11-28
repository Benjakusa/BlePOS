<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(MpesaCredential::class, 'mpesa_credential_id');
    }

    public function platformPayment(): BelongsTo
    {
        return $this->belongsTo(PlatformPayment::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
