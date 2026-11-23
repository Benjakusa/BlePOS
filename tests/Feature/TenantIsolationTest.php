<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenantContext;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_tenant_scope_restricts_queries_to_active_tenant(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Tenant Alpha']);
        $tenantB = Tenant::factory()->create(['name' => 'Tenant Beta']);

        $shopA1 = Shop::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Alpha Shop 1']);
        $shopA2 = Shop::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Alpha Shop 2']);
        $shopB1 = Shop::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Beta Shop 1']);

        // When TenantContext is set to Tenant A
        TenantContext::setTenant($tenantA);
        $shopsForA = Shop::all();

        $this->assertCount(2, $shopsForA);
        $this->assertTrue($shopsForA->contains('id', $shopA1->id));
        $this->assertTrue($shopsForA->contains('id', $shopA2->id));
        $this->assertFalse($shopsForA->contains('id', $shopB1->id));

        // When TenantContext is switched to Tenant B
        TenantContext::setTenant($tenantB);
        $shopsForB = Shop::all();

        $this->assertCount(1, $shopsForB);
        $this->assertTrue($shopsForB->contains('id', $shopB1->id));
        $this->assertFalse($shopsForB->contains('id', $shopA1->id));
    }

    public function test_creating_scoped_model_auto_populates_tenant_id(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::create([
            'name' => 'Automated Tenant Shop',
            'code' => 'AUTO01',
        ]);

        $this->assertEquals($tenant->id, $shop->tenant_id);
        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'tenant_id' => $tenant->id,
            'code' => 'AUTO01',
        ]);
    }

    public function test_resolve_tenant_context_middleware_resolves_via_domain(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'quickmart']);
        TenantDomain::create([
            'tenant_id' => $tenant->id,
            'domain' => 'pos.quickmart.co.ke',
            'is_primary' => true,
        ]);

        $request = Request::create('https://pos.quickmart.co.ke/app', 'GET');
        $middleware = new ResolveTenantContext;

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['tenant_id' => TenantContext::getTenantId()]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($tenant->id, $data['tenant_id']);
    }

    public function test_enforce_tenant_isolation_denies_cross_tenant_access(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->forTenant($tenantA)->create();

        Route::middleware(['web', 'tenant.isolate'])->get('/test/tenant-protected', function () {
            return response()->json(['status' => 'ok']);
        });

        // Simulating request with TenantContext set to Tenant B while User A is logged in
        TenantContext::setTenant($tenantB);

        $response = $this->actingAs($userA)->get('/test/tenant-protected');
        $response->assertForbidden();
    }

    public function test_enforce_tenant_isolation_denies_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);
        $user = User::factory()->forTenant($tenant)->create();

        Route::middleware(['web', 'tenant.isolate'])->get('/test/tenant-suspended-check', function () {
            return response()->json(['status' => 'ok']);
        });

        TenantContext::setTenant($tenant);

        $response = $this->actingAs($user)->get('/test/tenant-suspended-check');
        $response->assertForbidden();
    }
}
