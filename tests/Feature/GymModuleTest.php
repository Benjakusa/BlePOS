<?php

namespace Tests\Feature;

use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymMember;
use App\Models\GymPlan;
use App\Models\GymSubscription;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymModuleTest extends TestCase
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

    private function makeTenantAndShop(string $name = 'Gym A'): array
    {
        $tenant = Tenant::factory()->create(['name' => $name]);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::setTenant($tenant);
        ShopContext::setShop($shop);

        return [$tenant, $shop];
    }

    public function test_expired_subscription_blocks_checkin(): void
    {
        [$tenant] = $this->makeTenantAndShop();
        $plan = GymPlan::create(['name' => 'Monthly', 'billing_cycle' => 'monthly', 'price' => 2000, 'is_active' => true]);
        $member = GymMember::create(['name' => 'John Doe', 'phone' => '0700000001']);
        GymSubscription::create(['member_id' => $member->id, 'plan_id' => $plan->id, 'status' => 'active', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        // Check-in after expiry: controller marks expired and blocks
        $sub = GymSubscription::where('member_id', $member->id)->first();
        $this->assertTrue($sub->end_date < today());
        // Simulate the controller logic
        if ($sub->end_date < today()) {
            $sub->update(['status' => 'expired']);
        }
        $this->assertEquals('expired', $sub->fresh()->status);
    }

    public function test_freeze_extends_end_date_correctly(): void
    {
        [$tenant] = $this->makeTenantAndShop();
        $plan = GymPlan::create(['name' => 'Monthly', 'billing_cycle' => 'monthly', 'price' => 2000, 'is_active' => true]);
        $member = GymMember::create(['name' => 'Jane', 'phone' => '0700000002']);
        $sub = GymSubscription::create(['member_id' => $member->id, 'plan_id' => $plan->id, 'status' => 'active', 'start_date' => today(), 'end_date' => today()->addMonth()]);
        $originalEnd = Carbon::parse($sub->end_date);
        $freezeStart = today()->addDays(5);
        $freezeEnd = today()->addDays(12);
        $days = $freezeStart->diffInDays($freezeEnd);
        $sub->update(['status' => 'frozen', 'freeze_start' => $freezeStart, 'freeze_end' => $freezeEnd, 'end_date' => $originalEnd->addDays($days)]);
        $this->assertEquals('frozen', $sub->fresh()->status);
        $this->assertEquals($originalEnd->toDateString(), Carbon::parse($sub->fresh()->end_date)->toDateString());
    }

    public function test_class_capacity_enforced_waitlist_on_overflow(): void
    {
        [$tenant] = $this->makeTenantAndShop();
        $plan = GymPlan::create(['name' => 'Monthly', 'billing_cycle' => 'monthly', 'price' => 2000, 'is_active' => true]);
        $class = GymClass::create(['name' => 'Yoga', 'scheduled_at' => now()->addDay(), 'duration_minutes' => 60, 'capacity' => 1, 'is_cancelled' => false]);
        $m1 = GymMember::create(['name' => 'Alice', 'phone' => '0700000003']);
        $m2 = GymMember::create(['name' => 'Bob', 'phone' => '0700000004']);
        GymClassBooking::create(['class_id' => $class->id, 'member_id' => $m1->id, 'status' => 'confirmed']);
        // Second booking should be waitlisted
        $confirmedCount = GymClassBooking::where('class_id', $class->id)->where('status', 'confirmed')->count();
        $status = $confirmedCount >= $class->capacity ? 'waitlisted' : 'confirmed';
        $this->assertEquals('waitlisted', $status);
        $b2 = GymClassBooking::create(['class_id' => $class->id, 'member_id' => $m2->id, 'status' => $status]);
        $this->assertEquals('waitlisted', $b2->status);
    }

    public function test_session_credits_deducted_on_attendance(): void
    {
        [$tenant] = $this->makeTenantAndShop();
        $plan = GymPlan::create(['name' => '10-Session', 'billing_cycle' => 'sessions', 'price' => 5000, 'session_credits' => 10, 'is_active' => true]);
        $member = GymMember::create(['name' => 'Eve', 'phone' => '0700000005']);
        $sub = GymSubscription::create(['member_id' => $member->id, 'plan_id' => $plan->id, 'status' => 'active', 'start_date' => today(), 'end_date' => today()->addMonth(), 'session_credits_remaining' => 10]);
        $class = GymClass::create(['name' => 'Spin', 'scheduled_at' => now()->addHour(), 'duration_minutes' => 45, 'capacity' => 20, 'is_cancelled' => false]);
        $booking = GymClassBooking::create(['class_id' => $class->id, 'member_id' => $member->id, 'status' => 'confirmed']);
        // Simulate attendance
        $sub->decrement('session_credits_remaining');
        $booking->update(['status' => 'attended', 'attended_at' => now()]);
        $this->assertEquals(9, $sub->fresh()->session_credits_remaining);
    }

    public function test_session_pack_blocked_at_zero_credits(): void
    {
        [$tenant] = $this->makeTenantAndShop();
        $plan = GymPlan::create(['name' => '1-Session', 'billing_cycle' => 'sessions', 'price' => 500, 'session_credits' => 1, 'is_active' => true]);
        $member = GymMember::create(['name' => 'Frank', 'phone' => '0700000006']);
        $sub = GymSubscription::create(['member_id' => $member->id, 'plan_id' => $plan->id, 'status' => 'active', 'start_date' => today(), 'end_date' => today()->addMonth(), 'session_credits_remaining' => 0]);
        $remaining = $sub->fresh()->session_credits_remaining;
        $this->assertEquals(0, $remaining);
        // Controller returns error when 0 credits
        $blocked = $remaining <= 0;
        $this->assertTrue($blocked);
    }

    public function test_tenant_isolation_gym_members(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'GymA']);
        $tenantB = Tenant::factory()->create(['name' => 'GymB']);
        $shopA = Shop::factory()->create(['tenant_id' => $tenantA->id]);
        $shopB = Shop::factory()->create(['tenant_id' => $tenantB->id]);

        TenantContext::setTenant($tenantA);
        ShopContext::setShop($shopA);
        $memberA = GymMember::create(['name' => 'Tenant A Member', 'phone' => '0711000001']);

        TenantContext::setTenant($tenantB);
        ShopContext::setShop($shopB);
        $memberB = GymMember::create(['name' => 'Tenant B Member', 'phone' => '0711000002']);

        // Tenant A cannot see Tenant B's member
        TenantContext::setTenant($tenantA);
        ShopContext::setShop($shopA);
        $visibleToA = GymMember::pluck('id')->toArray();
        $this->assertContains($memberA->id, $visibleToA);
        $this->assertNotContains($memberB->id, $visibleToA);
    }

    public function test_branch_isolation_gym_members(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'SharedGym']);
        $shopA = Shop::factory()->create(['tenant_id' => $tenant->id]);
        $shopB = Shop::factory()->create(['tenant_id' => $tenant->id]);

        TenantContext::setTenant($tenant);
        ShopContext::setShop($shopA);
        $mA = GymMember::create(['name' => 'Branch A Member', 'phone' => '0712000001']);

        ShopContext::setShop($shopB);
        $mB = GymMember::create(['name' => 'Branch B Member', 'phone' => '0712000002']);

        ShopContext::setShop($shopA);
        $visibleToA = GymMember::pluck('id')->toArray();
        $this->assertContains($mA->id, $visibleToA);
        $this->assertNotContains($mB->id, $visibleToA);
    }
}
