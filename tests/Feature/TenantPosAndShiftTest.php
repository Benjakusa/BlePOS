<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPosAndShiftTest extends TestCase
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

    private function createCashierUser(Tenant $tenant, Shop $shop): User
    {
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
        ]);

        $permissions = [
            'pos.sale.create',
            'pos.sales.view',
            'pos.sale.void',
            'shifts.view',
            'shifts.manage',
            'shops.all.manage',
        ];

        foreach ($permissions as $p) {
            $perm = TenantPermission::firstOrCreate(['name' => $p], ['group' => 'pos', 'description' => $p]);
            $role->permissions()->attach($perm->id);
        }

        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($role->id);
        $user->shops()->attach($shop->id);

        return $user;
    }

    public function test_cashier_can_open_manage_and_close_shift(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'Main Till',
            'code' => 'TIL-01',
        ]);

        $cashier = $this->createCashierUser($tenant, $shop);

        // 0. Render create shift view
        $createViewResponse = $this->actingAs($cashier)->get(route('tenant.shifts.create'));
        $this->assertSame($shop->id, session('active_shop_id'));
        $this->assertSame(200, $createViewResponse->status(), $createViewResponse->getContent());
        $createViewResponse->assertSee('Open Work Shift');
        $createViewResponse->assertSee('Main Till');

        // 1. Open shift
        $openResponse = $this->actingAs($cashier)->post(route('tenant.shifts.store'), [
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'opening_float' => '2500.00',
            'notes' => 'Morning shift',
        ]);

        $shift = CashierShift::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->where('user_id', $cashier->id)->first();
        $this->assertNotNull($shift);
        $this->assertEquals('open', $shift->status);
        $this->assertEquals('2500.00', $shift->opening_float);
        $openResponse->assertRedirect(route('tenant.shifts.show', $shift));

        // 2. Cannot open second shift concurrently
        $secondOpen = $this->actingAs($cashier)->post(route('tenant.shifts.store'), [
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'opening_float' => '1000.00',
        ]);
        $secondOpen->assertStatus(422);

        // 3. Record cash-in / cash-out movement
        $this->actingAs($cashier)->post(route('tenant.shifts.cash-movement', $shift), [
            'type' => 'in',
            'amount' => '500.00',
            'reason' => 'Additional change added',
        ]);

        $shift = CashierShift::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->findOrFail($shift->id);
        $this->assertEquals('500.00', $shift->cash_in);
        $this->assertEquals('3000.00', $shift->expected_cash);

        // 4. Close shift with counted cash
        $closeResponse = $this->actingAs($cashier)->post(route('tenant.shifts.close', $shift), [
            'actual_cash' => '3050.00',
            'closing_notes' => 'Shift ended with +50 surplus',
        ]);

        $closeResponse->assertRedirect(route('tenant.shifts.show', $shift));
        $shift = CashierShift::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->findOrFail($shift->id);
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals('3050.00', $shift->actual_cash);
        $this->assertEquals('50.00', $shift->variance);
    }

    public function test_cashier_can_process_sale_and_view_receipt(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'POS Register 1',
            'code' => 'REG-1',
        ]);

        $cashier = $this->createCashierUser($tenant, $shop);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Milk 500ml',
            'sku' => 'MLK-500',
            'cost_price' => 50.00,
            'selling_price' => 65.00,
            'tax_rate' => 16.00,
            'track_stock' => true,
        ]);

        // Stock initial quantity: 50
        InventoryStock::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'quantity' => 50.0,
        ]);

        // Cashier opens shift
        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'shift_number' => 'SH-TEST-01',
            'status' => 'open',
            'opening_float' => '1000.00',
            'opened_at' => now(),
        ]);

        // A client cannot complete an underpaid sale or override the product price.
        $underpaid = $this->actingAs($cashier)->postJson(route('tenant.pos.process'), [
            'shift_id' => $shift->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 0.01]],
            'payments' => [['method' => 'cash', 'amount' => 0.02]],
        ]);
        $underpaid->assertStatus(422);
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(50.0, (float) InventoryStock::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->where('product_id', $product->id)->value('quantity'));

        // Process POS sale: server price for 2 packets of milk @ 65 = 130.
        $saleResponse = $this->actingAs($cashier)->postJson(route('tenant.pos.process'), [
            'shift_id' => $shift->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 0.01,
                    'tax_rate' => 0,
                ],
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => 150.00,
                ],
            ],
        ]);

        $saleResponse->assertOk();
        $saleResponse->assertJson([
            'success' => true,
            'grand_total' => '130.00',
            'change_due' => '20.00',
        ]);

        $saleId = $saleResponse->json('sale_id');
        $sale = Sale::withoutGlobalScopes()->where('tenant_id', $tenant->id)->find($saleId);
        $this->assertNotNull($sale);
        $this->assertEquals('completed', $sale->status);

        // Verify stock deducted from 50 to 48
        $currentStock = InventoryStock::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->where('product_id', $product->id)->first();
        $this->assertEquals(48.0, (float) $currentStock->quantity);

        // Verify shift cash_sales updated
        $shift = CashierShift::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('shop_id', $shop->id)->findOrFail($shift->id);
        $this->assertEquals('130.00', $shift->cash_sales);

        // Verify receipt view renders correctly
        $receiptResponse = $this->actingAs($cashier)->get(route('tenant.pos.receipt', $sale));
        $receiptResponse->assertOk();
        $receiptResponse->assertSee($sale->sale_number);
        $receiptResponse->assertSee('Milk 500ml');
        $receiptResponse->assertSee('130.00');

        // Test voiding the sale
        $voidResponse = $this->actingAs($cashier)->post(route('tenant.pos.void', $sale), [
            'void_reason' => 'Customer changed mind',
        ]);
        $voidResponse->assertRedirect(route('tenant.pos.sales'));

        $sale->refresh();
        $this->assertEquals('voided', $sale->status);

        // Verify stock was restored to 50
        $currentStock->refresh();
        $this->assertEquals(50.0, (float) $currentStock->quantity);
    }
}
