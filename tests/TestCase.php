<?php

namespace Tests;

use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::allowAllTenants();
        ShopContext::allowAllShops();
    }

    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($user instanceof User) {
            if (! $user->mfa_confirmed_at) {
                $user->forceFill(['mfa_confirmed_at' => now()])->saveQuietly();
            }
            $activeShopId = $user->shops()->withoutGlobalScopes()->wherePivot('is_primary', true)->value('shops.id')
                ?? $user->shops()->withoutGlobalScopes()->value('shops.id');
            $this->withSession([
                Auth::guard('web')->getName() => $user->getAuthIdentifier(),
                'tenant_mfa_verified_at' => now()->timestamp,
                'active_shop_id' => $activeShopId,
            ]);
        }

        return parent::actingAs($user, $guard);
    }
}
