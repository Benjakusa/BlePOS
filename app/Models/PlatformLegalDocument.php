<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformLegalDocument extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'version',
        'content',
        'changelog',
        'is_published',
        'effective_from',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'effective_from' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'created_by_user_id');
    }
}
