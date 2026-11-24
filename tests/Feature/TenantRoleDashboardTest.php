<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleDashboardTest extends TestCase
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

    private function createTenantWithShops(string $industry = 'retail', string $email = 'owner@example.com'): array
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'industry' => $industry,
            'owner_email' => $email,
            'owner_name' => 'Tenant Owner',
        ]);

        $shopA = Shop::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nairobi CBD Branch',
            'code' => 'NBO-'.uniqid(),
        ]);

        $shopB = Shop::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Westlands Branch',
            'code' => 'WST-'.uniqid(),
        ]);

        $registerA = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'name' => 'Till 1',
            'code' => 'TIL-'.uniqid(),
            'is_active' => true,
        ]);

        $registerB = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopB->id,
            'name' => 'Till B1',
            'code' => 'TIL-'.uniqid(),
            'is_active' => true,
        ]);

        return [$tenant, $shopA, $shopB, $registerA, $registerB];
    }

    public function test_unauthenticated_user_cannot_access_tenant_dashboard(): void
    {
        $response = $this->get(route('tenant.dashboard'));
        $response->assertRedirect(route('platform.login'));
    }

    public function test_tenant_owner_sees_executive_dashboard_and_multi_shop_performance(): void
    {
        [$tenant, $shopA, $shopB, $registerA] = $this->createTenantWithShops('retail', 'owner-main@example.com');
        TenantContext::setTenant($tenant);

        $ownerRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);

        $owner = User::factory()->forTenant($tenant)->create([
            'email' => $tenant->owner_email,
        ]);
        $owner->roles()->attach($ownerRole->id);

        // Create completed sales in Shop A
        $sale = Sale::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'register_id' => $registerA->id,
            'user_id' => $owner->id,
            'sale_number' => 'SAL-1001',
            'receipt_number' => 'REC-1001',
            'subtotal' => 2000.00,
            'tax_total' => 320.00,
            'grand_total' => 2320.00,
            'status' => 'completed',
        ]);

        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method' => 'mpesa',
            'amount' => 2320.00,
            'reference' => 'MPESA123',
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertSee('Executive Overview');
        $response->assertSee('Gross Sales');
        $response->assertSee('Net Sales (Excl. Tax)');
        $response->assertSee('Multi-Shop Branch Performance');
        $response->assertSee('Nairobi CBD Branch');
        $response->assertSee('Westlands Branch');
        $response->assertSee('Business Entity &amp; Subscription Plan', false);
    }

    public function test_shop_manager_sees_shop_scoped_dashboard(): void
    {
        [$tenant, $shopA, $shopB, $registerA] = $this->createTenantWithShops('retail', 'owner-mgr@example.com');
        TenantContext::setTenant($tenant);

        $managerRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Shop Manager',
            'slug' => 'shop-manager',
        ]);
        $perm = TenantPermission::firstOrCreate(['name' => 'inventory.manage'], ['group' => 'inventory', 'description' => 'Inventory Manage']);
        $managerRole->permissions()->attach($perm->id);

        $manager = User::factory()->forTenant($tenant)->create([
            'email' => 'manager@example.com',
        ]);
        $manager->roles()->attach($managerRole->id);
        $manager->shops()->attach($shopA->id);

        // Open shift in Shop A
        CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'register_id' => $registerA->id,
            'user_id' => $manager->id,
            'status' => 'open',
            'opening_float' => 1500.00,
            'cash_sales' => 500.00,
            'opened_at' => now(),
        ]);

        // Inventory item in Shop A with low stock
        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Beverages',
            'slug' => 'beverages',
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Mineral Water 500ml',
            'sku' => 'WAT-500',
            'selling_price' => 50.00,
            'reorder_level' => 10,
            'track_stock' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'product_id' => $product->id,
            'quantity' => 2.0000,
        ]);

        $response = $this->actingAs($manager)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertSee('Shop Manager');
        $response->assertSee('Active Registers &amp; Shifts', false);
        $response->assertSee('Branch Stock Alerts');
        $response->assertSee('Mineral Water 500ml');
        // Shop Manager cannot see cross-branch comparison
        $response->assertDontSee('Multi-Shop Branch Performance');
    }

    public function test_cashier_with_open_shift_sees_terminal_dashboard_with_strict_restrictions(): void
    {
        [$tenant, $shopA, $shopB, $registerA] = $this->createTenantWithShops('retail', 'owner-csh@example.com');
        TenantContext::setTenant($tenant);

        $cashierRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
        ]);
        $perm = TenantPermission::firstOrCreate(['name' => 'pos.checkout'], ['group' => 'pos', 'description' => 'POS Checkout']);
        $cashierRole->permissions()->attach($perm->id);

        $cashier = User::factory()->forTenant($tenant)->create([
            'email' => 'cashier@example.com',
        ]);
        $cashier->roles()->attach($cashierRole->id);
        $cashier->shops()->attach($shopA->id);

        // Open shift for cashier
        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'register_id' => $registerA->id,
            'user_id' => $cashier->id,
            'status' => 'open',
            'opening_float' => 3000.00,
            'cash_sales' => 1200.00,
            'mpesa_sales' => 800.00,
            'opened_at' => now(),
        ]);

        // Sale in this shift
        Sale::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopA->id,
            'register_id' => $registerA->id,
            'cashier_shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'sale_number' => 'SAL-CSH-01',
            'receipt_number' => 'REC-CSH-01',
            'subtotal' => 2000.00,
            'tax_total' => 0.00,
            'grand_total' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($cashier)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertSee('Cashier Terminal');
        $response->assertSee('Active Shift Open');
        $response->assertSee('Opening Float');
        $response->assertSee('Cash Sales in Shift');
        $response->assertSee('SAL-CSH-01');

        // Cashier restrictions: no profit margins, no cross-shop comparisons
        $response->assertDontSee('Multi-Shop Branch Performance');
        $response->assertDontSee('Net Sales (Excl. Tax)');
        $response->assertDontSee('Business Entity &amp; Subscription Plan', false);
    }

    public function test_cashier_without_open_shift_sees_prompt_to_open_shift(): void
    {
        [$tenant, $shopA, $shopB, $registerA] = $this->createTenantWithShops('retail', 'owner-csh2@example.com');
        TenantContext::setTenant($tenant);

        $cashierRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
        ]);

        $cashier = User::factory()->forTenant($tenant)->create([
            'email' => 'cashier2@example.com',
        ]);
        $cashier->roles()->attach($cashierRole->id);
        $cashier->shops()->attach($shopA->id);

        $response = $this->actingAs($cashier)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertSee('No Active Cashier Shift');
        $response->assertSee('Open Shift &amp; Start Register', false);
    }

    public function test_shop_switching_updates_active_shop_and_metrics(): void
    {
        [$tenant, $shopA, $shopB, $registerA, $registerB] = $this->createTenantWithShops('retail', 'owner-switch@example.com');
        TenantContext::setTenant($tenant);

        $ownerRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);

        $owner = User::factory()->forTenant($tenant)->create([
            'email' => $tenant->owner_email,
        ]);
        $owner->roles()->attach($ownerRole->id);

        // Sale only in Shop B
        Sale::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shopB->id,
            'register_id' => $registerB->id,
            'user_id' => $owner->id,
            'sale_number' => 'SAL-B-01',
            'receipt_number' => 'REC-B-01',
            'subtotal' => 7500.00,
            'tax_total' => 0.00,
            'grand_total' => 7500.00,
            'status' => 'completed',
        ]);

        // Filter by Shop B
        $responseB = $this->actingAs($owner)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard', ['shop_id' => $shopB->id]));

        $responseB->assertOk();
        $responseB->assertSee('KES 7,500.00');

        // Filter by Shop A (0 sales)
        $responseA = $this->actingAs($owner)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard', ['shop_id' => $shopA->id]));

        $responseA->assertOk();
        $responseA->assertSee('KES 0.00');
    }

    public function test_industry_blueprint_alerts_render_for_pharmacy_and_hospitality(): void
    {
        // Pharmacy tenant
        [$tenantPharm, $shopPharm] = $this->createTenantWithShops('pharmacy', 'owner-pharm@example.com');
        TenantContext::setTenant($tenantPharm);

        $ownerPharm = User::factory()->forTenant($tenantPharm)->create([
            'email' => $tenantPharm->owner_email,
        ]);
        $rolePharm = TenantRole::create([
            'tenant_id' => $tenantPharm->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);
        $ownerPharm->roles()->attach($rolePharm->id);

        $responsePharm = $this->actingAs($ownerPharm)
            ->withSession(['tenant_id' => $tenantPharm->id])
            ->get(route('tenant.dashboard'));

        $responsePharm->assertOk();
        $responsePharm->assertSee('Pharmacy Compliance &amp; Controlled Substances', false);
        $responsePharm->assertSee('PHARMACY RAILS ACTIVE');

        // Restaurant tenant
        TenantContext::clear();
        ShopContext::clear();

        [$tenantRest, $shopRest] = $this->createTenantWithShops('restaurant', 'owner-rest@example.com');
        TenantContext::setTenant($tenantRest);

        $ownerRest = User::factory()->forTenant($tenantRest)->create([
            'email' => $tenantRest->owner_email,
        ]);
        $roleRest = TenantRole::create([
            'tenant_id' => $tenantRest->id,
            'name' => 'Owner',
            'slug' => 'owner',
        ]);
        $ownerRest->roles()->attach($roleRest->id);

        $responseRest = $this->actingAs($ownerRest)
            ->withSession(['tenant_id' => $tenantRest->id])
            ->get(route('tenant.dashboard'));

        $responseRest->assertOk();
        $responseRest->assertSee('Hospitality &amp; Table Service', false);
        $responseRest->assertSee('HOSPITALITY RAILS');
    }
}
