<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantInventoryTest extends TestCase
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

    private function createInventoryManager(Tenant $tenant, Shop $shop): User
    {
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Storekeeper',
            'slug' => 'storekeeper',
        ]);

        $permissions = [
            'inventory.view',
            'inventory.adjust',
            'inventory.receive',
            'shops.all.manage',
        ];

        foreach ($permissions as $p) {
            $perm = TenantPermission::firstOrCreate(['name' => $p], ['group' => 'inventory', 'description' => $p]);
            $role->permissions()->attach($perm->id);
        }

        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($role->id);
        $user->shops()->attach($shop->id);

        return $user;
    }

    public function test_storekeeper_can_view_stock_levels(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $user = $this->createInventoryManager($tenant, $shop);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sugar 1kg',
            'sku' => 'SGR-01',
            'cost_price' => 120.00,
            'selling_price' => 150.00,
            'track_stock' => true,
            'reorder_level' => 10,
        ]);

        InventoryStock::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'quantity' => 5.0, // Low stock
        ]);

        $response = $this->actingAs($user)->get(route('tenant.inventory.index', ['low_stock' => 1]));
        $response->assertOk();
        $response->assertSee('Sugar 1kg');
        $response->assertSee('Low Stock');
    }

    public function test_storekeeper_can_receive_stock_with_immutable_movement(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $user = $this->createInventoryManager($tenant, $shop);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cooking Oil 2L',
            'sku' => 'OIL-02L',
            'cost_price' => 500.00,
            'selling_price' => 620.00,
            'track_stock' => true,
        ]);

        $response = $this->actingAs($user)->post(route('tenant.inventory.receive.store'), [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'quantity' => 20,
            'cost_price' => 495.00,
            'reference_number' => 'DEL-2026-001',
            'notes' => 'Direct from Bidco refinery',
        ]);

        $response->assertRedirect(route('tenant.inventory.index', ['shop_id' => $shop->id]));

        // Check stock quantity updated
        $stock = InventoryStock::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('product_id', $product->id)->where('shop_id', $shop->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(20.0, (float) $stock->quantity);

        // Check immutable StockMovement record created
        $movement = StockMovement::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('receipt', $movement->type);
        $this->assertEquals(0.0, (float) $movement->balance_before);
        $this->assertEquals(20.0, (float) $movement->quantity);
        $this->assertEquals(20.0, (float) $movement->balance_after);
        $this->assertEquals('DEL-2026-001', $movement->reference_id);
    }

    public function test_storekeeper_can_adjust_stock_with_reason(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $user = $this->createInventoryManager($tenant, $shop);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Eggs Tray 30s',
            'sku' => 'EGG-30',
            'cost_price' => 380.00,
            'selling_price' => 450.00,
            'track_stock' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'quantity' => 15.0,
        ]);

        // Adjust for breakage: -2 trays
        $response = $this->actingAs($user)->post(route('tenant.inventory.adjust.store'), [
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'delta' => -2,
            'type' => 'damage',
            'reason' => 'Dropped during shelf restocking',
        ]);

        $response->assertRedirect(route('tenant.inventory.index', ['shop_id' => $shop->id]));

        $stock = InventoryStock::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('product_id', $product->id)->where('shop_id', $shop->id)->first();
        $this->assertEquals(13.0, (float) $stock->quantity);

        $movement = StockMovement::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->where('product_id', $product->id)->latest()->first();
        $this->assertEquals('damage', $movement->type);
        $this->assertEquals(15.0, (float) $movement->balance_before);
        $this->assertEquals(-2.0, (float) $movement->quantity);
        $this->assertEquals(13.0, (float) $movement->balance_after);
        $this->assertEquals('Dropped during shelf restocking', $movement->reason);

        // Verify movements audit ledger renders
        $movementsView = $this->actingAs($user)->get(route('tenant.inventory.movements'));
        $movementsView->assertOk();
        $movementsView->assertSee('Eggs Tray 30s');
        $movementsView->assertSee('Dropped during shelf restocking');
    }

    public function test_tenant_cannot_adjust_inventory_of_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::setTenant($tenantA);
        $shopA = Shop::factory()->create(['tenant_id' => $tenantA->id]);
        $userA = $this->createInventoryManager($tenantA, $shopA);

        TenantContext::setTenant($tenantB);
        $shopB = Shop::factory()->create(['tenant_id' => $tenantB->id]);
        $productB = Product::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Secret Item',
            'sku' => 'TB-01',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ]);

        TenantContext::setTenant($tenantA);
        $response = $this->actingAs($userA)->post(route('tenant.inventory.adjust.store'), [
            'product_id' => $productB->id,
            'shop_id' => $shopB->id,
            'delta' => 10,
            'type' => 'adjustment',
            'reason' => 'Hacking attempt',
        ]);

        // Cross-tenant resource request must be rejected (404 via TenantScope or 403)
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
