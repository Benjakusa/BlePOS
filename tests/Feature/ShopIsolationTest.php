<?php

namespace Tests\Feature;

use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ShopIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
        ShopContext::clear();

        Route::middleware(['web', 'tenant.isolate', 'shop.isolate'])->get('/test/shops/{shop}/pos', function (Shop $shop) {
            return response()->json([
                'shop_id' => $shop->id,
                'active_context_shop_id' => ShopContext::getShopId(),
            ]);
        });
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        ShopContext::clear();
        parent::tearDown();
    }

    public function test_cashier_can_access_assigned_shop_and_is_denied_access_to_unassigned_shop(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shopA = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Branch A']);
        $shopB = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Branch B']);

        $cashier = User::factory()->forTenant($tenant)->create();
        $cashier->shops()->attach($shopA->id, ['is_primary' => true]);

        // Cashier accesses assigned Shop A -> OK (200)
        $responseA = $this->actingAs($cashier)->get("/test/shops/{$shopA->id}/pos");
        $responseA->assertOk();
        $this->assertEquals($shopA->id, $responseA->json('shop_id'));

        // Cashier attempts to access unassigned Shop B -> Denied (403)
        $responseB = $this->actingAs($cashier)->get("/test/shops/{$shopB->id}/pos");
        $responseB->assertForbidden();
    }

    public function test_user_cannot_access_shop_belonging_to_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $shopA = Shop::factory()->create(['tenant_id' => $tenantA->id]);
        $shopB = Shop::factory()->create(['tenant_id' => $tenantB->id]);

        $userA = User::factory()->forTenant($tenantA)->create();
        $userA->shops()->attach($shopA->id, ['is_primary' => true]);

        TenantContext::setTenant($tenantA);

        // Attempting to access Shop B (which belongs to Tenant B):
        // Route model binding with TenantScope safely reports 404 (does not leak existence)
        $response = $this->actingAs($userA)->get("/test/shops/{$shopB->id}/pos");
        $response->assertNotFound();
    }

    public function test_multi_shop_manager_can_access_all_shops_within_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shopA = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $shopB = Shop::factory()->create(['tenant_id' => $tenant->id]);

        $manager = User::factory()->forTenant($tenant)->create();
        $permission = TenantPermission::create([
            'name' => 'shops.all.manage',
            'group' => 'shops',
            'description' => 'Manage all branch locations',
        ]);
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operations Manager',
            'slug' => 'operations-manager',
        ]);
        $role->permissions()->attach($permission->id);
        $manager->roles()->attach($role->id);

        $responseA = $this->actingAs($manager)->get("/test/shops/{$shopA->id}/pos");
        $responseA->assertOk();

        $responseB = $this->actingAs($manager)->get("/test/shops/{$shopB->id}/pos");
        $responseB->assertOk();
    }

    public function test_shop_scope_isolates_registers_per_shop(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shopA = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $shopB = Shop::factory()->create(['tenant_id' => $tenant->id]);

        $regA1 = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shopA->id, 'name' => 'Lane 1', 'code' => 'L1']);
        $regA2 = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shopA->id, 'name' => 'Lane 2', 'code' => 'L2']);
        $regB1 = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shopB->id, 'name' => 'Lane 1', 'code' => 'L1']);

        // Scope to Shop A
        ShopContext::setShop($shopA);
        $registersA = Register::all();
        $this->assertCount(2, $registersA);
        $this->assertTrue($registersA->contains('id', $regA1->id));
        $this->assertTrue($registersA->contains('id', $regA2->id));
        $this->assertFalse($registersA->contains('id', $regB1->id));

        // Scope to Shop B
        ShopContext::setShop($shopB);
        $registersB = Register::all();
        $this->assertCount(1, $registersB);
        $this->assertTrue($registersB->contains('id', $regB1->id));
    }
}
