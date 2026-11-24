<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryTemplate extends Model
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
            'is_active' => 'boolean',
            'default_modules' => 'array',
            'tax_rates' => 'array',
            'default_roles' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * Tenants provisioned with this industry template.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'industry', 'slug');
    }
}
