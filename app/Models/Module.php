<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dependencies' => 'array',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
            'settings_schema' => 'array',
        ];
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(TenantModuleEntitlement::class);
    }
}
