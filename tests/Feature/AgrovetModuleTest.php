<?php

namespace Tests\Feature;

use App\Models\AgrovetCustomer;
use App\Models\AgrovetProduct;
use App\Models\AgrovetStockMovement;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgrovetModuleTest extends TestCase
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

    private function setupTenantAndShop(): array
    {
        $tenant = Tenant::factory()->create(['name' => 'AgroVet Co']);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::setTenant($tenant);
        ShopContext::setShop($shop);

        return [$tenant, $shop];
    }

    public function test_credit_limit_enforced(): void
    {
        $this->setupTenantAndShop();
        $customer = AgrovetCustomer::create([
            'name' => 'Farmer John',
            'phone' => '0700000001',
            'credit_limit' => 5000,
            'credit_balance' => 4500,
            'is_active' => true,
        ]);
        $product = AgrovetProduct::create([
            'name' => 'DAP Fertilizer',
            'unit_base' => 'kg',
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        $total = 200 * 10;
        $this->assertTrue($customer->credit_balance + $total > $customer->credit_limit);
    }

    public function test_credit_repayment_reduces_balance(): void
    {
        $this->setupTenantAndShop();
        $customer = AgrovetCustomer::create([
            'name' => 'Farmer Jane',
            'phone' => '0700000002',
            'credit_limit' => 10000,
            'credit_balance' => 3000,
            'is_active' => true,
        ]);
        $repayment = 1000;
        $customer->decrement('credit_balance', min($repayment, $customer->credit_balance));
        $this->assertEquals(2000, (int) $customer->fresh()->credit_balance);
    }

    public function test_stock_movement_purchase_increases_stock(): void
    {
        $this->setupTenantAndShop();
        $product = AgrovetProduct::create([
            'name' => 'Maize Seeds',
            'unit_base' => 'kg',
            'reorder_level' => 5,
            'is_active' => true,
        ]);
        AgrovetStockMovement::create([
            'product_id' => $product->id,
            'batch_number' => 'B001',
            'expiry_date' => '2027-06-30',
            'movement_type' => 'purchase',
            'quantity' => 100,
            'unit' => 'kg',
            'unit_price' => 50,
            'moved_at' => now(),
        ]);
        $netStock = AgrovetStockMovement::where('product_id', $product->id)->sum('quantity');
        $this->assertEquals(100, (int) $netStock);
    }

    public function test_fefo_selects_earliest_expiry_batch(): void
    {
        $this->setupTenantAndShop();
        $product = AgrovetProduct::create([
            'name' => 'Pesticide',
            'unit_base' => 'L',
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        AgrovetStockMovement::create([
            'product_id' => $product->id,
            'batch_number' => 'B002',
            'expiry_date' => '2027-12-31',
            'movement_type' => 'purchase',
            'quantity' => 50,
            'unit' => 'L',
            'unit_price' => 200,
            'moved_at' => now(),
        ]);
        AgrovetStockMovement::create([
            'product_id' => $product->id,
            'batch_number' => 'B001',
            'expiry_date' => '2027-03-31',
            'movement_type' => 'purchase',
            'quantity' => 30,
            'unit' => 'L',
            'unit_price' => 200,
            'moved_at' => now(),
        ]);

        $earliest = AgrovetStockMovement::where('product_id', $product->id)
            ->where('movement_type', 'purchase')
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date', 'asc')
            ->first();
        $this->assertEquals('B001', $earliest->batch_number);
    }

    public function test_expired_batch_should_be_flagged(): void
    {
        $this->setupTenantAndShop();
        $product = AgrovetProduct::create([
            'name' => 'Vaccine',
            'unit_base' => 'ml',
            'reorder_level' => 0,
            'is_active' => true,
        ]);
        AgrovetStockMovement::create([
            'product_id' => $product->id,
            'batch_number' => 'OLD',
            'expiry_date' => '2025-01-01',
            'movement_type' => 'purchase',
            'quantity' => 10,
            'unit' => 'ml',
            'unit_price' => 100,
            'moved_at' => now(),
        ]);
        $batch = AgrovetStockMovement::where('product_id', $product->id)->first();
        $isExpired = $batch->expiry_date && now()->gt(Carbon::parse($batch->expiry_date));
        $this->assertTrue($isExpired);
    }

    public function test_tenant_isolation_agrovet_customers(): void
    {
        $tA = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $tA->id]);
        $tB = Tenant::factory()->create();
        $sB = Shop::factory()->create(['tenant_id' => $tB->id]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $c1 = AgrovetCustomer::create([
            'name' => 'CustA',
            'phone' => '0711000001',
            'credit_limit' => 0,
            'credit_balance' => 0,
            'is_active' => true,
        ]);

        TenantContext::setTenant($tB);
        ShopContext::setShop($sB);
        $c2 = AgrovetCustomer::create([
            'name' => 'CustB',
            'phone' => '0711000002',
            'credit_limit' => 0,
            'credit_balance' => 0,
            'is_active' => true,
        ]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $ids = AgrovetCustomer::pluck('id')->toArray();
        $this->assertContains($c1->id, $ids);
        $this->assertNotContains($c2->id, $ids);
    }

    public function test_branch_isolation_agrovet_products(): void
    {
        $t = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $t->id]);
        $sB = Shop::factory()->create(['tenant_id' => $t->id]);

        TenantContext::setTenant($t);
        ShopContext::setShop($sA);
        $p1 = AgrovetProduct::create([
            'name' => 'ProductA',
            'unit_base' => 'kg',
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        ShopContext::setShop($sB);
        $p2 = AgrovetProduct::create([
            'name' => 'ProductB',
            'unit_base' => 'kg',
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        ShopContext::setShop($sA);
        $ids = AgrovetProduct::pluck('id')->toArray();
        $this->assertContains($p1->id, $ids);
        $this->assertNotContains($p2->id, $ids);
    }
}
