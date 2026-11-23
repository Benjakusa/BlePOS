<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorePosAndInventoryTest extends TestCase
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

    public function test_inventory_service_manages_stock_with_immutable_audit_movements(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->forTenant($tenant)->create();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unga Maize Meal 2kg',
            'sku' => 'UNGA-02KG',
            'cost_price' => 180.00,
            'selling_price' => 220.00,
            'track_stock' => true,
        ]);

        $inventory = app(InventoryService::class);

        // 1. Initial stock receipt
        $movement1 = $inventory->receiveStock($product, $shop, 50, $user, 'PO-001');
        $this->assertEquals(50.0, $inventory->getStock($product, $shop));
        $this->assertEquals(0.0, (float) $movement1->balance_before);
        $this->assertEquals(50.0, (float) $movement1->balance_after);
        $this->assertEquals('receipt', $movement1->type);

        // 2. Stock adjustment
        $movement2 = $inventory->adjustStock($product, $shop, -5, 'adjustment', $user, 'Damaged during transit');
        $this->assertEquals(45.0, $inventory->getStock($product, $shop));
        $this->assertEquals(50.0, (float) $movement2->balance_before);
        $this->assertEquals(45.0, (float) $movement2->balance_after);

        // Verify audit log has 2 records
        $this->assertCount(2, StockMovement::where('product_id', $product->id)->get());
    }

    public function test_cashier_shift_lifecycle_and_variance_reconciliation(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $register = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'name' => 'Lane 1', 'code' => 'L1']);
        $cashier = User::factory()->forTenant($tenant)->create();

        // 1. Open shift with 5,000 KES float
        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'status' => 'open',
            'opening_float' => 5000.00,
            'cash_sales' => 12500.00,
            'mpesa_sales' => 8400.00,
            'opened_at' => now(),
        ]);

        $this->assertTrue($shift->isOpen());

        // Expected cash = opening float (5000) + cash sales (12500) = 17500
        $this->assertEquals('17500.00', $shift->calculateExpectedCash());

        // 2. Close shift with actual cash counted 17,450 (variance: -50 short)
        $expected = $shift->calculateExpectedCash();
        $actual = '17450.00';
        $variance = bcsub($actual, $expected, 2);

        $shift->update([
            'status' => 'closed',
            'expected_cash' => $expected,
            'actual_cash' => $actual,
            'variance' => $variance,
            'closed_at' => now(),
            'closing_notes' => 'Minor cash shortage of 50 KES.',
        ]);

        $this->assertTrue($shift->fresh()->isClosed());
        $this->assertEquals('-50.00', (string) $shift->fresh()->variance);
    }

    public function test_pos_sale_creation_with_split_tender_and_automated_stock_deduction(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $register = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'name' => 'Lane 1', 'code' => 'L1']);
        $cashier = User::factory()->forTenant($tenant)->create();

        $inventory = app(InventoryService::class);
        $saleService = app(SaleService::class);

        // Create product with 20 in stock
        $productA = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cooking Oil 1L',
            'sku' => 'OIL-01L',
            'cost_price' => 250.00,
            'selling_price' => 320.00,
            'tax_rate' => 16.00,
            'tax_type' => 'inclusive',
            'track_stock' => true,
        ]);
        $inventory->receiveStock($productA, $shop, 20, $cashier);

        // Open cashier shift
        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $cashier->id,
            'status' => 'open',
            'opening_float' => 2000.00,
            'opened_at' => now(),
        ]);

        // Sell 3 units of Cooking Oil (320 * 3 = 960 total)
        // Split payment: 500 KES via M-Pesa, 500 KES Cash (40 KES change due)
        $items = [
            [
                'product_id' => $productA->id,
                'quantity' => 3,
                'unit_price' => 320.00,
                'discount' => 0.00,
            ],
        ];

        $payments = [
            [
                'method' => 'mpesa',
                'amount' => 500.00,
                'reference' => 'QJK9812A09',
            ],
            [
                'method' => 'cash',
                'amount' => 500.00,
            ],
        ];

        $sale = $saleService->createSale(
            shop: $shop,
            register: $register,
            user: $cashier,
            items: $items,
            payments: $payments,
            shift: $shift,
            notes: 'Customer paid via M-Pesa + Cash'
        );

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals('960.00', (string) $sale->grand_total);
        $this->assertEquals('1000.00', (string) $sale->paid_total);
        $this->assertEquals('40.00', (string) $sale->change_due);

        // Assert Stock Deducted from 20 down to 17
        $this->assertEquals(17.0, $inventory->getStock($productA, $shop));

        // Assert Shift sales updated
        $shift->refresh();
        $this->assertEquals('500.00', (string) $shift->mpesa_sales);
        $this->assertEquals('460.00', (string) $shift->cash_sales); // 500 cash in - 40 change = 460 net cash

        // Assert StockMovement recorded with type 'sale'
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $productA->id,
            'type' => 'sale',
            'quantity' => -3.0000,
            'balance_after' => 17.0000,
        ]);
    }

    public function test_void_sale_reverses_stock_deduction(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $register = Register::create(['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'name' => 'Lane 1', 'code' => 'L1']);
        $cashier = User::factory()->forTenant($tenant)->create();

        $inventory = app(InventoryService::class);
        $saleService = app(SaleService::class);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bread 400g',
            'sku' => 'BRD-400G',
            'cost_price' => 50.00,
            'selling_price' => 65.00,
            'track_stock' => true,
        ]);
        $inventory->receiveStock($product, $shop, 10, $cashier);

        // Make sale of 2 units (stock goes from 10 to 8)
        $sale = $saleService->createSale(
            shop: $shop,
            register: $register,
            user: $cashier,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            payments: [['method' => 'cash', 'amount' => 130.00]]
        );

        $this->assertEquals(8.0, $inventory->getStock($product, $shop));

        // Void the sale
        $voidedSale = $saleService->voidSale($sale, $cashier, 'Wrong product rung by cashier');

        $this->assertEquals('voided', $voidedSale->status);

        // Assert stock returned to 10
        $this->assertEquals(10.0, $inventory->getStock($product, $shop));

        // Assert void movement logged
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'void',
            'quantity' => 2.0000,
            'balance_after' => 10.0000,
        ]);
    }
}
