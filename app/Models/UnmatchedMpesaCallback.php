<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnmatchedMpesaCallback extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(MpesaCredential::class, 'mpesa_credential_id');
    }
}
