<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCatalogTest extends TestCase
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

    private function createTenantUserWithPermissions(Tenant $tenant, array $permissionNames): User
    {
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        foreach ($permissionNames as $permName) {
            $perm = TenantPermission::firstOrCreate([
                'name' => $permName,
            ], [
                'group' => 'catalog',
                'description' => $permName,
            ]);
            $role->permissions()->attach($perm->id);
        }

        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_tenant_user_can_view_and_create_categories(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $user = $this->createTenantUserWithPermissions($tenant, ['catalog.categories.manage']);

        $response = $this->actingAs($user)->get(route('tenant.categories.index'));
        $response->assertOk();
        $response->assertSee('Categories');

        $storeResponse = $this->actingAs($user)->post(route('tenant.categories.store'), [
            'name' => 'Beverages & Soft Drinks',
            'slug' => 'beverages',
            'description' => 'Cold drinks and sodas',
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $storeResponse->assertRedirect(route('tenant.categories.index'));
        $this->assertDatabaseHas('categories', [
            'tenant_id' => $tenant->id,
            'name' => 'Beverages & Soft Drinks',
            'slug' => 'beverages',
        ]);
    }

    public function test_tenant_cannot_see_or_modify_other_tenant_categories(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::setTenant($tenantA);
        $userA = $this->createTenantUserWithPermissions($tenantA, ['catalog.categories.manage']);

        TenantContext::setTenant($tenantB);
        $categoryB = Category::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Secret Category',
            'slug' => 'tenant-b-secret',
        ]);

        TenantContext::setTenant($tenantA);
        $response = $this->actingAs($userA)->get(route('tenant.categories.index'));
        $response->assertOk();
        $response->assertDontSee('Tenant B Secret Category');

        // Attempt to edit Category B as User A should result in 404 (due to TenantScope) or 403
        $editResponse = $this->actingAs($userA)->get(route('tenant.categories.edit', $categoryB));
        $this->assertTrue(in_array($editResponse->status(), [403, 404]));
    }

    public function test_tenant_user_can_create_and_lookup_products(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $user = $this->createTenantUserWithPermissions($tenant, [
            'catalog.products.manage',
            'catalog.categories.manage',
        ]);

        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Grain & Flour',
            'slug' => 'grain-flour',
        ]);

        $storeResponse = $this->actingAs($user)->post(route('tenant.products.store'), [
            'name' => 'Pembe Maize Flour 2kg',
            'sku' => 'PEMBE-2KG',
            'barcode' => '616110123456',
            'category_id' => $category->id,
            'cost_price' => 170.00,
            'selling_price' => 210.00,
            'tax_rate' => 16.00,
            'unit' => 'packet',
            'track_stock' => 1,
            'is_active' => 1,
        ]);

        $storeResponse->assertRedirect(route('tenant.products.index'));
        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'sku' => 'PEMBE-2KG',
            'barcode' => '616110123456',
        ]);

        // Test barcode lookup endpoint for POS checkout
        $lookupResponse = $this->actingAs($user)->get(route('tenant.products.lookup', [
            'barcode' => '616110123456',
        ]));

        $lookupResponse->assertOk();
        $lookupResponse->assertJson([
            'found' => true,
            'product' => [
                'name' => 'Pembe Maize Flour 2kg',
                'sku' => 'PEMBE-2KG',
                'selling_price' => '210.00',
            ],
        ]);
    }
}
