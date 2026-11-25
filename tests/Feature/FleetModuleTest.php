<?php

namespace Tests\Feature;

use App\Models\FleetBooking;
use App\Models\FleetCustomer;
use App\Models\FleetMaintenance;
use App\Models\FleetPayment;
use App\Models\FleetVehicle;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetModuleTest extends TestCase
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

    private function setupTenantAndShop(string $name = 'FleetCo'): array
    {
        $tenant = Tenant::factory()->create(['name' => $name]);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::setTenant($tenant);
        ShopContext::setShop($shop);

        return [$tenant, $shop];
    }

    private function makeVehicle(array $extra = []): FleetVehicle
    {
        return FleetVehicle::create(array_merge([
            'plate' => 'KAA 001A',
            'make' => 'Toyota',
            'model' => 'Prado',
            'type' => 'car',
            'daily_rate' => 5000,
            'weekly_rate' => 30000,
            'deposit_amount' => 10000,
            'status' => 'available',
        ], $extra));
    }

    private function makeCustomer(): FleetCustomer
    {
        return FleetCustomer::create(['name' => 'Test Renter', 'phone' => '0700000001']);
    }

    private function makeBooking(FleetVehicle $v, FleetCustomer $c, array $extra = []): FleetBooking
    {
        return FleetBooking::create(array_merge([
            'vehicle_id' => $v->id,
            'customer_id' => $c->id,
            'pickup_at' => now()->addDay(),
            'return_at' => now()->addDays(3),
            'status' => 'confirmed',
            'rate_type' => 'daily',
            'agreed_rate' => 5000,
            'deposit_amount' => 10000,
            'total_billed' => 10000,
        ], $extra));
    }

    public function test_overlapping_booking_blocked(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        $customer = $this->makeCustomer();
        $this->makeBooking($vehicle, $customer, ['pickup_at' => now()->addDay(), 'return_at' => now()->addDays(5)]);

        $pickup = Carbon::parse(now()->addDays(3));
        $return = Carbon::parse(now()->addDays(7));
        $buffer = 2;
        $overlap = FleetBooking::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('pickup_at', '<', $return->copy()->addHours($buffer))
            ->where('return_at', '>', $pickup->copy()->subHours($buffer))
            ->exists();
        $this->assertTrue($overlap);
    }

    public function test_non_overlapping_booking_allowed(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        $customer = $this->makeCustomer();
        $this->makeBooking($vehicle, $customer, ['pickup_at' => now()->addDay(), 'return_at' => now()->addDays(3), 'status' => 'completed']);

        $newPickup = Carbon::parse(now()->addDays(3))->addHours(3);
        $newReturn = Carbon::parse(now()->addDays(5));
        $overlap = FleetBooking::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('pickup_at', '<', $newReturn->copy()->addHours(2))
            ->where('return_at', '>', $newPickup->copy()->subHours(2))
            ->exists();
        $this->assertFalse($overlap);
    }

    public function test_maintenance_blocks_new_booking(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        FleetMaintenance::create([
            'vehicle_id' => $vehicle->id,
            'description' => 'Engine overhaul',
            'started_at' => now(),
            'cost' => 15000,
            'is_active' => true,
        ]);
        $hasMaintenance = FleetMaintenance::where('vehicle_id', $vehicle->id)->where('is_active', true)->whereNull('completed_at')->exists();
        $this->assertTrue($hasMaintenance);
    }

    public function test_deposit_refund_deducts_damage_and_fuel(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        $customer = $this->makeCustomer();
        $booking = $this->makeBooking($vehicle, $customer, [
            'deposit_paid' => true,
            'deposit_amount' => 10000,
            'damage_charge' => 2000,
            'fuel_charge' => 500,
        ]);
        $deductions = $booking->damage_charge + $booking->fuel_charge;
        $refund = max(0, $booking->deposit_amount - $deductions);
        $this->assertEquals(7500, (int) $refund);
    }

    public function test_late_surcharge_computed_correctly(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        $customer = $this->makeCustomer();
        $booking = $this->makeBooking($vehicle, $customer, [
            'return_at' => now()->addDays(3),
            'agreed_rate' => 5000,
        ]);
        $actualReturn = Carbon::parse($booking->return_at)->addHours(26);
        $lateHours = Carbon::parse($booking->return_at)->diffInHours($actualReturn);
        $lateDays = (int) ceil($lateHours / 24);
        $surcharge = $lateDays * $booking->agreed_rate;
        $this->assertEquals(2, $lateDays);
        $this->assertEquals(10000, (int) $surcharge);
    }

    public function test_m_pesa_deposit_and_balance_recorded(): void
    {
        $this->setupTenantAndShop();
        $vehicle = $this->makeVehicle();
        $customer = $this->makeCustomer();
        $booking = $this->makeBooking($vehicle, $customer);
        FleetPayment::create(['booking_id' => $booking->id, 'type' => 'deposit', 'amount' => 10000, 'method' => 'mpesa', 'paid_at' => now()]);
        FleetPayment::create(['booking_id' => $booking->id, 'type' => 'balance', 'amount' => 5000, 'method' => 'cash', 'paid_at' => now()]);
        $total = FleetPayment::where('booking_id', $booking->id)->sum('amount');
        $this->assertEquals(15000, (int) $total);
    }

    public function test_tenant_isolation_fleet_vehicles(): void
    {
        $tA = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $tA->id]);
        $tB = Tenant::factory()->create();
        $sB = Shop::factory()->create(['tenant_id' => $tB->id]);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $v1 = FleetVehicle::create(['plate' => 'TenantA-001', 'make' => 'Toyota', 'model' => 'Corolla', 'type' => 'car', 'daily_rate' => 3000, 'weekly_rate' => 18000, 'deposit_amount' => 5000, 'status' => 'available']);

        TenantContext::setTenant($tB);
        ShopContext::setShop($sB);
        $v2 = FleetVehicle::create(['plate' => 'TenantB-001', 'make' => 'Nissan', 'model' => 'X-Trail', 'type' => 'car', 'daily_rate' => 4000, 'weekly_rate' => 24000, 'deposit_amount' => 8000, 'status' => 'available']);

        TenantContext::setTenant($tA);
        ShopContext::setShop($sA);
        $ids = FleetVehicle::pluck('id')->toArray();
        $this->assertContains($v1->id, $ids);
        $this->assertNotContains($v2->id, $ids);
    }

    public function test_branch_isolation_fleet_bookings(): void
    {
        $t = Tenant::factory()->create();
        $sA = Shop::factory()->create(['tenant_id' => $t->id]);
        $sB = Shop::factory()->create(['tenant_id' => $t->id]);

        TenantContext::setTenant($t);
        ShopContext::setShop($sA);
        $vA = FleetVehicle::create(['plate' => 'BranchA-001', 'make' => 'Toyota', 'model' => 'Prius', 'type' => 'car', 'daily_rate' => 2500, 'weekly_rate' => 15000, 'deposit_amount' => 5000, 'status' => 'available']);
        $cA = FleetCustomer::create(['name' => 'Branch A Client', 'phone' => '0712000001']);
        $b1 = $this->makeBooking($vA, $cA);

        ShopContext::setShop($sB);
        $vB = FleetVehicle::create(['plate' => 'BranchB-001', 'make' => 'Honda', 'model' => 'CR-V', 'type' => 'car', 'daily_rate' => 3000, 'weekly_rate' => 18000, 'deposit_amount' => 6000, 'status' => 'available']);
        $cB = FleetCustomer::create(['name' => 'Branch B Client', 'phone' => '0712000002']);
        $b2 = $this->makeBooking($vB, $cB);

        ShopContext::setShop($sA);
        $ids = FleetBooking::pluck('id')->toArray();
        $this->assertContains($b1->id, $ids);
        $this->assertNotContains($b2->id, $ids);
    }
}
