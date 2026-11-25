<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantModuleEntitlement;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
        ShopContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        ShopContext::clear();
        parent::tearDown();
    }

    private function createTenantUser(string $industry = 'retail'): array
    {
        $tenant = Tenant::factory()->create(['industry' => $industry]);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->shops()->attach($shop->id, ['is_primary' => true]);

        return [$tenant, $shop, $user];
    }

    public function test_access_denied_when_module_is_not_enabled(): void
    {
        [$tenant, $shop, $user] = $this->createTenantUser('retail');

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.gym.index'));

        $response->assertForbidden();
    }

    public function test_access_allowed_when_module_is_entitled(): void
    {
        [$tenant, $shop, $user] = $this->createTenantUser('retail');

        $module = Module::query()->firstOrCreate(
            ['slug' => 'gym'],
            ['name' => 'Gym & Fitness', 'category' => 'industry', 'dependencies' => [], 'is_core' => false]
        );

        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.gym.index'));

        $response->assertOk();
    }

    public function test_access_allowed_when_industry_matches(): void
    {
        [$tenant, $shop, $user] = $this->createTenantUser('gym-fitness');

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.gym.index'));

        $response->assertOk();
    }

    public function test_non_pharmacy_tenant_gets_forbidden_on_pharmacy_routes_without_entitlement(): void
    {
        [$tenant, $shop, $user] = $this->createTenantUser('retail');

        $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.pharmacy.batches.index'))
            ->assertForbidden();
    }
}
