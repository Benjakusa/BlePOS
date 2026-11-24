<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformEtimsCertification extends Model
{
    protected $fillable = [
        'component_name',
        'certification_stage',
        'status',
        'details',
        'kra_reference',
        'verified_at',
        'verified_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'verified_by_user_id');
    }
}
