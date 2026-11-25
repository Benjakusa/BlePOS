<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => 'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
        'recovery_code_hashes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_reset_password' => 'boolean',
            'totp_secret' => 'encrypted',
            'recovery_code_hashes' => 'array',
            'mfa_confirmed_at' => 'datetime',
        ];
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_user')->withPivot('is_primary')->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(TenantRole::class, 'tenant_role_user')->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isTenantOwner(): bool
    {
        if ($this->hasRole('owner')) {
            return true;
        }

        if ($this->tenant && strtolower($this->email) === strtolower($this->tenant->owner_email)) {
            return true;
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isTenantOwner()) {
            return true;
        }

        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    public function isAssignedToShop(Shop|int $shop): bool
    {
        $shopId = $shop instanceof Shop ? $shop->id : $shop;

        return $this->shops->contains('id', $shopId);
    }

    public function canAccessShop(Shop|int $shop): bool
    {
        $shopId = $shop instanceof Shop ? $shop->id : $shop;

        if ($this->hasPermission('shops.all.manage') || $this->hasRole('owner')) {
            return true;
        }

        return $this->isAssignedToShop($shopId);
    }
}
