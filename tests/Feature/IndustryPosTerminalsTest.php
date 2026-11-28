<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\FleetBooking;
use App\Models\FleetVehicle;
use App\Models\GarageJobCard;
use App\Models\GymMember;
use App\Models\GymPayment;
use App\Models\Parcel;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndustryPosTerminalsTest extends TestCase
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

    private function setupTerminalEnvironment(string $industry = 'retail'): array
    {
        $tenant = Tenant::factory()->create([
            'name' => 'FastTrack Logistics & Multi-Services',
            'industry' => $industry,
            'currency' => 'KES',
        ]);
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Central Hub']);
        $destShop = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Mombasa Port Branch']);
        ShopContext::setShop($shop);

        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operator',
            'slug' => 'operator',
        ]);

        $permissions = [
            'pos.sale.create',
            'pos.sales.view',
            'shifts.view',
            'shifts.manage',
        ];

        foreach ($permissions as $p) {
            $perm = TenantPermission::firstOrCreate(['name' => $p], ['group' => 'pos', 'description' => $p]);
            $role->permissions()->attach($perm->id);
        }

        $user = User::factory()->forTenant($tenant)->create(['name' => 'Alex Operator']);
        $user->roles()->attach($role->id);
        $user->shops()->attach($shop->id, ['is_primary' => true]);

        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'Counter Terminal 1',
            'code' => 'TERM-01',
        ]);

        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_number' => 'SH-INIT',
            'status' => 'open',
            'opening_float' => 2000,
            'cash_sales' => 0,
            'mpesa_sales' => 0,
            'expected_cash' => 2000,
            'opened_at' => now(),
        ]);

        return [$tenant, $shop, $destShop, $user, $shift];
    }

    public function test_pos_terminal_mode_switching(): void
    {
        [$tenant, $shop, $destShop, $user, $shift] = $this->setupTerminalEnvironment();

        // Retail Mode
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.checkout', ['mode' => 'retail']));
        $response->assertOk();
        $response->assertSee('Retail Checkout');

        // Parcels Mode
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.checkout', ['mode' => 'parcels']));
        $response->assertOk();
        $response->assertSee('Parcel Counter Intake');

        // Gym Mode
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.checkout', ['mode' => 'gym']));
        $response->assertOk();
        $response->assertSee('Gym Front Desk Terminal');

        // Fleet Mode
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.checkout', ['mode' => 'fleet']));
        $response->assertOk();
        $response->assertSee('Fleet Car Hire Dispatch Terminal');

        // Garage Mode
        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.checkout', ['mode' => 'garage']));
        $response->assertOk();
        $response->assertSee('Auto Garage Service Counter');
    }

    public function test_parcel_counter_intake_records_parcel_and_updates_cashier_shift(): void
    {
        [$tenant, $shop, $destShop, $user, $shift] = $this->setupTerminalEnvironment('parcel-delivery');

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.pos.parcels.intake'), [
                'destination_shop_id' => $destShop->id,
                'sender_name' => 'Baraka Traders',
                'sender_phone' => '0711223344',
                'recipient_name' => 'Coast Supplies',
                'recipient_phone' => '0755667788',
                'contents' => 'Box of Computer Accessories',
                'weight_kg' => 3.5,
                'service_level' => 'express',
                'fee' => 750.00,
                'payment_method' => 'cash',
            ]);

        TenantContext::setTenant($tenant);
        $parcel = Parcel::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($parcel);
        $this->assertEquals('Baraka Traders', $parcel->sender_name);
        $this->assertEquals(750.00, (float) $parcel->fee);
        $this->assertStringStartsWith('WB-', $parcel->tracking_number);

        // Check active cashier shift was incremented
        $shift->refresh();
        $this->assertEquals(750.00, (float) $shift->cash_sales);
        $this->assertEquals(2750.00, (float) $shift->expected_cash);

        $response->assertRedirect(route('tenant.pos.waybill', $parcel));

        // Test printable waybill view
        $waybillResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.waybill', $parcel));

        $waybillResponse->assertOk();
        $waybillResponse->assertSee($parcel->tracking_number);
        $waybillResponse->assertSee('OFFICIAL WAYBILL RECEIPT');
        $waybillResponse->assertSee('Baraka Traders');
        $waybillResponse->assertSee('Coast Supplies');
    }

    public function test_gym_front_desk_checkin_and_day_pass_sales(): void
    {
        [$tenant, $shop, $destShop, $user, $shift] = $this->setupTerminalEnvironment('gym-fitness');

        // Day Pass Purchase
        $dayPassResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.pos.gym.action'), [
                'action_type' => 'day_pass',
                'name' => 'Kevin Gymnast',
                'phone' => '0722334455',
                'amount' => 500.00,
                'payment_method' => 'cash',
            ]);

        $dayPassResponse->assertRedirect();

        TenantContext::setTenant($tenant);
        $member = GymMember::where('tenant_id', $tenant->id)->where('phone', '0722334455')->first();
        $this->assertNotNull($member);
        $this->assertEquals('Kevin Gymnast', $member->name);

        $payment = GymPayment::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(500.00, (float) $payment->amount);

        // Shift cash sales incremented
        $shift->refresh();
        $this->assertEquals(500.00, (float) $shift->cash_sales);

        // Test printable admission slip
        $slipResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.gym-slip', $payment));

        $slipResponse->assertOk();
        $slipResponse->assertSee('FRONT DESK ADMISSION PASS');
        $slipResponse->assertSee('Kevin Gymnast');
        $slipResponse->assertSee('KES 500.00');

        // Existing Member Check-in
        $checkinResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.pos.gym.action'), [
                'action_type' => 'checkin',
                'member_id' => $member->id,
            ]);

        $checkinResponse->assertRedirect();
        TenantContext::setTenant($tenant);
        $this->assertDatabaseHas('gym_checkins', [
            'tenant_id' => $tenant->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_fleet_dispatch_records_booking_deposit_and_slip(): void
    {
        [$tenant, $shop, $destShop, $user, $shift] = $this->setupTerminalEnvironment('car-hire-fleet');

        $vehicle = FleetVehicle::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'plate' => 'KDG 456Z',
            'make' => 'Toyota',
            'model' => 'Prado TX',
            'year' => 2022,
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.pos.fleet.dispatch'), [
                'vehicle_id' => $vehicle->id,
                'customer_name' => 'Harrison Ford Kenya',
                'customer_phone' => '0700112233',
                'id_number' => '98765432',
                'license_number' => 'DL-998877',
                'pickup_at' => now()->format('Y-m-d H:i:s'),
                'return_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'agreed_rate' => 12000.00,
                'deposit_amount' => 30000.00,
                'payment_method' => 'cash',
            ]);

        TenantContext::setTenant($tenant);
        $booking = FleetBooking::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(30000.00, (float) $booking->deposit_amount);

        // Vehicle marked booked
        $vehicle->refresh();
        $this->assertEquals('booked', $vehicle->status);

        // Shift updated
        $shift->refresh();
        $this->assertEquals(30000.00, (float) $shift->cash_sales);

        $response->assertRedirect(route('tenant.pos.fleet-slip', $booking));

        // Test gate pass slip view
        $slipResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.fleet-slip', $booking));

        $slipResponse->assertOk();
        $slipResponse->assertSee('DISPATCH GATE PASS &amp; HANDOVER', false);
        $slipResponse->assertSee('KDG 456Z');
        $slipResponse->assertSee('Harrison Ford Kenya');
        $slipResponse->assertSee('KES 30,000.00');
    }

    public function test_garage_service_counter_records_job_card_estimate_and_slip(): void
    {
        [$tenant, $shop, $destShop, $user, $shift] = $this->setupTerminalEnvironment('auto-garage');

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.pos.garage.intake'), [
                'plate' => 'KCX 789A',
                'make' => 'Subaru',
                'model' => 'Forester XT',
                'customer_name' => 'Wambui Ngugi',
                'customer_phone' => '0733445566',
                'mileage_in' => 89500,
                'service_notes' => 'Engine check light on, CVT transmission oil change requested.',
                'labour_estimate' => 6500.00,
                'deposit_amount' => 5000.00,
                'payment_method' => 'cash',
            ]);

        TenantContext::setTenant($tenant);
        $jobCard = GarageJobCard::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($jobCard);
        $this->assertEquals(89500, $jobCard->mileage_in);
        $this->assertStringStartsWith('JC-', $jobCard->job_number);

        // Shift updated
        $shift->refresh();
        $this->assertEquals(5000.00, (float) $shift->cash_sales);

        $response->assertRedirect(route('tenant.pos.garage-slip', $jobCard));

        // Test printable job card view
        $slipResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.pos.garage-slip', $jobCard));

        $slipResponse->assertOk();
        $slipResponse->assertSee('JOB CARD INTAKE &amp; WORK ORDER', false);
        $slipResponse->assertSee('KCX 789A');
        $slipResponse->assertSee('Wambui Ngugi');
        $slipResponse->assertSee('89,500 KM');
    }
}
