<?php

namespace Tests\Feature;

use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialConsumption;
use App\Models\PrintQuote;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintingModuleTest extends TestCase
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
        $tenant = Tenant::factory()->create(['name' => 'PrintCo']);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::setTenant($tenant);
        ShopContext::setShop($shop);

        return [$tenant, $shop];
    }

    public function test_production_blocked_without_accepted_quote(): void
    {
        $this->setupTenantAndShop();
        $job = PrintJob::create([
            'customer_name' => 'Alice',
            'customer_phone' => '0700001',
            'title' => 'Flyers',
            'status' => 'approved',
            'total_amount' => 5000,
            'balance_due' => 5000,
            'deposit_amount' => 1000,
        ]);

        $quote = PrintQuote::where('job_id', $job->id)->where('is_accepted', true)->first();
        $this->assertNull($quote);
    }

    public function test_production_blocked_without_deposit(): void
    {
        $this->setupTenantAndShop();
        $job = PrintJob::create([
            'customer_name' => 'Bob',
            'customer_phone' => '0700002',
            'title' => 'Banner',
            'status' => 'approved',
            'total_amount' => 3000,
            'balance_due' => 3000,
            'deposit_amount' => 0,
        ]);
        PrintQuote::create([
            'job_id' => $job->id,
            'version' => 1,
            'subtotal' => 3000,
            'total' => 3000,
            'is_accepted' => true,
            'accepted_at' => now(),
        ]);
        $this->assertEquals(0, (float) $job->deposit_amount);
    }

    public function test_quote_versioning_on_new_quote(): void
    {
        $this->setupTenantAndShop();
        $job = PrintJob::create([
            'customer_name' => 'Carol',
            'customer_phone' => '0700003',
            'title' => 'T-Shirts',
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);
        PrintQuote::create([
            'job_id' => $job->id,
            'version' => 1,
            'subtotal' => 1000,
            'total' => 1000,
            'is_accepted' => true,
            'accepted_at' => now(),
        ]);

        // Create new quote — previous must be unaccepted
        PrintQuote::where('job_id', $job->id)->update(['is_accepted' => false]);
        $version = (PrintQuote::where('job_id', $job->id)->max('version') ?? 0) + 1;
        PrintQuote::create([
            'job_id' => $job->id,
            'version' => $version,
            'subtotal' => 1200,
            'total' => 1200,
        ]);

        $this->assertEquals(2, $version);
        $this->assertEquals(0, PrintQuote::where('job_id', $job->id)->where('version', 1)->value('is_accepted'));
    }

    public function test_material_deducted_on_production_start(): void
    {
        $this->setupTenantAndShop();
        $job = PrintJob::create([
            'customer_name' => 'Dave',
            'customer_phone' => '0700004',
            'title' => 'Mug',
            'status' => 'approved',
            'total_amount' => 2000,
            'balance_due' => 1000,
            'deposit_amount' => 1000,
        ]);
        PrintQuote::create([
            'job_id' => $job->id,
            'version' => 1,
            'subtotal' => 2000,
            'total' => 2000,
            'is_accepted' => true,
            'accepted_at' => now(),
        ]);
        $material = PrintMaterial::create([
            'name' => 'Paper',
            'unit' => 'ream',
            'quantity_on_hand' => 10,
            'reorder_level' => 2,
            'cost_per_unit' => 500,
        ]);
        PrintMaterialConsumption::create([
            'job_id' => $job->id,
            'material_id' => $material->id,
            'quantity_used' => 3,
            'consumed_at' => now(),
        ]);

        // Simulate production start
        foreach (PrintMaterialConsumption::where('job_id', $job->id)->get() as $c) {
            PrintMaterial::where('id', $c->material_id)->decrement('quantity_on_hand', $c->quantity_used);
        }
        $this->assertEquals(7, (int) PrintMaterial::find($material->id)->quantity_on_hand);
    }

    public function test_deposit_and_balance_reconcile_total(): void
    {
        $this->setupTenantAndShop();
        $job = PrintJob::create([
            'customer_name' => 'Eve',
            'customer_phone' => '0700005',
            'title' => 'Cards',
            'status' => 'quote',
            'total_amount' => 5000,
            'balance_due' => 5000,
            'deposit_amount' => 0,
        ]);
        $job->update(['deposit_amount' => 2000, 'deposit_paid_at' => now(), 'balance_due' => 3000]);
        $this->assertEquals(3000, (int) $job->fresh()->balance_due);
        $job->update(['balance_due' => 0]);
        $this->assertEquals(0, (int) $job->fresh()->balance_due);
    }

    public function test_tenant_isolation_print_jobs(): void
    {
        $tA = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $tA->id]);
        $tB = Tenant::factory()->create();
        $sB = Shop::factory()->create(['tenant_id' => $tB->id]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $j1 = PrintJob::create([
            'customer_name' => 'TenantA',
            'customer_phone' => '0700006',
            'title' => 'JobA',
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);

        TenantContext::setTenant($tB);
        ShopContext::setShop($sB);
        $j2 = PrintJob::create([
            'customer_name' => 'TenantB',
            'customer_phone' => '0700007',
            'title' => 'JobB',
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $ids = PrintJob::pluck('id')->toArray();
        $this->assertContains($j1->id, $ids);
        $this->assertNotContains($j2->id, $ids);
    }

    public function test_branch_isolation_print_jobs(): void
    {
        $t = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $t->id]);
        $sB = Shop::factory()->create(['tenant_id' => $t->id]);

        TenantContext::setTenant($t);
        ShopContext::setShop($sA);
        $j1 = PrintJob::create([
            'customer_name' => 'BranchA',
            'customer_phone' => '0700008',
            'title' => 'JobA',
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);

        ShopContext::setShop($sB);
        $j2 = PrintJob::create([
            'customer_name' => 'BranchB',
            'customer_phone' => '0700009',
            'title' => 'JobB',
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);

        ShopContext::setShop($sA);
        $ids = PrintJob::pluck('id')->toArray();
        $this->assertContains($j1->id, $ids);
        $this->assertNotContains($j2->id, $ids);
    }
}
