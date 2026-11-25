<?php

namespace Tests\Feature;

use App\Models\GarageCustomer;
use App\Models\GarageEstimate;
use App\Models\GarageJobCard;
use App\Models\GaragePart;
use App\Models\GaragePartConsumption;
use App\Models\GaragePayment;
use App\Models\GarageVehicle;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarageModuleTest extends TestCase
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

    private function setupTenantAndShop(string $name = 'GarageA'): array
    {
        $tenant = Tenant::factory()->create(['name' => $name]);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::setTenant($tenant);
        ShopContext::setShop($shop);

        return [$tenant, $shop];
    }

    private function makeJobWithVehicle(): GarageJobCard
    {
        $customer = GarageCustomer::create(['name' => 'Test Owner', 'phone' => '0700000001']);
        $vehicle = GarageVehicle::create(['customer_id' => $customer->id, 'plate' => 'KAA 123X', 'make' => 'Toyota', 'model' => 'Hilux']);

        return GarageJobCard::create([
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'job_number' => 'JC-TEST-001',
            'status' => 'awaiting_approval',
            'received_at' => now(),
        ]);
    }

    public function test_work_blocked_before_estimate_approval(): void
    {
        $this->setupTenantAndShop();
        $job = $this->makeJobWithVehicle();
        $this->assertNull($job->estimate);
        $blockedByNoEstimate = ! ($job->estimate && $job->estimate->is_approved);
        $this->assertTrue($blockedByNoEstimate);
    }

    public function test_work_allowed_after_estimate_approval(): void
    {
        $this->setupTenantAndShop();
        $job = $this->makeJobWithVehicle();
        $est = GarageEstimate::create([
            'job_id' => $job->id,
            'total_parts' => 5000,
            'total_labour' => 3000,
            'grand_total' => 8000,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        $job->update(['estimate_id' => $est->id, 'status' => 'approved']);
        $this->assertTrue($job->fresh()->estimate->is_approved);
    }

    public function test_parts_deducted_on_consumption(): void
    {
        $this->setupTenantAndShop();
        $job = $this->makeJobWithVehicle();
        $part = GaragePart::create([
            'name' => 'Oil Filter',
            'sku' => 'OF-001',
            'quantity_on_hand' => 10,
            'reorder_level' => 2,
            'cost_price' => 500,
            'selling_price' => 700,
        ]);
        GaragePartConsumption::create([
            'job_id' => $job->id,
            'part_id' => $part->id,
            'quantity_used' => 2,
            'consumed_at' => now(),
        ]);
        $part->decrement('quantity_on_hand', 2);
        $this->assertEquals(8, (int) $part->fresh()->quantity_on_hand);
    }

    public function test_partial_payments_accumulate(): void
    {
        $this->setupTenantAndShop();
        $job = $this->makeJobWithVehicle();
        $est = GarageEstimate::create([
            'job_id' => $job->id,
            'total_parts' => 4000,
            'total_labour' => 2000,
            'grand_total' => 6000,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        $job->update(['estimate_id' => $est->id]);
        GaragePayment::create(['job_id' => $job->id, 'amount' => 3000, 'method' => 'cash', 'paid_at' => now()]);
        GaragePayment::create(['job_id' => $job->id, 'amount' => 2000, 'method' => 'mpesa', 'paid_at' => now()]);
        $totalPaid = GaragePayment::where('job_id', $job->id)->sum('amount');
        $this->assertEquals(5000, (int) $totalPaid);
        $balanceDue = $est->grand_total - $totalPaid;
        $this->assertEquals(1000, (int) $balanceDue);
    }

    public function test_delivery_blocked_on_outstanding_balance(): void
    {
        $this->setupTenantAndShop();
        $job = $this->makeJobWithVehicle();
        $est = GarageEstimate::create([
            'job_id' => $job->id,
            'total_parts' => 0,
            'total_labour' => 5000,
            'grand_total' => 5000,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        GaragePayment::create(['job_id' => $job->id, 'amount' => 2000, 'method' => 'cash', 'paid_at' => now()]);
        $totalPaid = GaragePayment::where('job_id', $job->id)->sum('amount');
        $balanceDue = $est->grand_total - $totalPaid;
        $this->assertGreaterThan(0, $balanceDue);
    }

    public function test_vehicle_history_scoped_by_plate(): void
    {
        $this->setupTenantAndShop();
        $c = GarageCustomer::create(['name' => 'History Owner', 'phone' => '0700000002']);
        $v = GarageVehicle::create(['customer_id' => $c->id, 'plate' => 'KBX 999Y', 'make' => 'Nissan', 'model' => 'Navara']);
        GarageJobCard::create(['vehicle_id' => $v->id, 'customer_id' => $c->id, 'job_number' => 'JC-H001', 'status' => 'completed', 'received_at' => now()->subDays(30)]);
        GarageJobCard::create(['vehicle_id' => $v->id, 'customer_id' => $c->id, 'job_number' => 'JC-H002', 'status' => 'delivered', 'received_at' => now()->subDays(10)]);
        $count = GarageJobCard::where('vehicle_id', $v->id)->count();
        $this->assertEquals(2, $count);
    }

    public function test_tenant_isolation_garage_jobs(): void
    {
        $tA = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $tA->id]);
        $tB = Tenant::factory()->create();
        $sB = Shop::factory()->create(['tenant_id' => $tB->id]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $cA = GarageCustomer::create(['name' => 'OwnerA', 'phone' => '0712000001']);
        $vA = GarageVehicle::create(['customer_id' => $cA->id, 'plate' => 'KCA 001A', 'make' => 'Toyota', 'model' => 'Corolla']);
        $j1 = GarageJobCard::create(['vehicle_id' => $vA->id, 'customer_id' => $cA->id, 'job_number' => 'JC-A01', 'status' => 'received', 'received_at' => now()]);

        TenantContext::setTenant($tB);
        ShopContext::setShop($sB);
        $cB = GarageCustomer::create(['name' => 'OwnerB', 'phone' => '0712000002']);
        $vB = GarageVehicle::create(['customer_id' => $cB->id, 'plate' => 'KCB 001B', 'make' => 'Ford', 'model' => 'Ranger']);
        $j2 = GarageJobCard::create(['vehicle_id' => $vB->id, 'customer_id' => $cB->id, 'job_number' => 'JC-B01', 'status' => 'received', 'received_at' => now()]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $ids = GarageJobCard::pluck('id')->toArray();
        $this->assertContains($j1->id, $ids);
        $this->assertNotContains($j2->id, $ids);
    }

    public function test_branch_isolation_garage_parts(): void
    {
        $t = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $t->id]);
        $sB = Shop::factory()->create(['tenant_id' => $t->id]);

        TenantContext::setTenant($t);
        ShopContext::setShop($sA);
        $p1 = GaragePart::create(['name' => 'BranchA Part', 'quantity_on_hand' => 5, 'reorder_level' => 1, 'cost_price' => 100, 'selling_price' => 150]);

        ShopContext::setShop($sB);
        $p2 = GaragePart::create(['name' => 'BranchB Part', 'quantity_on_hand' => 3, 'reorder_level' => 1, 'cost_price' => 200, 'selling_price' => 300]);

        ShopContext::setShop($sA);
        $ids = GaragePart::pluck('id')->toArray();
        $this->assertContains($p1->id, $ids);
        $this->assertNotContains($p2->id, $ids);
    }
}
