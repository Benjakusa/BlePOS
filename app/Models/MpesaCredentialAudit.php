<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaCredentialAudit extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function credential(): BelongsTo
    {
        return $this->belongsTo(MpesaCredential::class, 'mpesa_credential_id');
    }
}
