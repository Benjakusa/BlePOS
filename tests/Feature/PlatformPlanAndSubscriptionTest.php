<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformPlanAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    private function createPlatformAdmin(string $roleSlug = 'billing-administrator'): array
    {
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role->id);

        $sessionId = Str::random(40);
        PlatformSession::create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$user, $sessionId];
    }

    public function test_billing_admin_can_view_create_and_update_plans(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('billing-administrator');

        // 1. View plans listing
        $response = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.plans.index'));

        $response->assertOk();
        $response->assertSee('Subscription Plans');

        // 2. Create new plan
        $createResponse = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.plans.store'), [
                'code' => 'enterprise-pro',
                'name' => 'Enterprise Pro',
                'description' => 'Unlimited shops and registers for growing chains',
                'currency' => 'KES',
                'monthly_price' => 15000.00,
                'annual_price' => 150000.00,
                'trial_days' => 30,
                'max_shops' => 20,
                'max_users' => 100,
                'max_registers' => 50,
                'max_warehouses' => 10,
                'is_active' => 1,
            ]);

        $createResponse->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseHas('plans', [
            'code' => 'enterprise-pro',
            'name' => 'Enterprise Pro',
            'monthly_price' => '15000.00',
        ]);

        $plan = Plan::where('code', 'enterprise-pro')->first();
        $this->assertNotNull($plan);

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'plan.create',
            'target_type' => Plan::class,
            'target_id' => (string) $plan->id,
            'actor_id' => $user->id,
        ]);

        // 3. Update plan
        $updateResponse = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->put(route('platform.plans.update', $plan), [
                'name' => 'Enterprise Pro Max',
                'description' => 'Updated description',
                'monthly_price' => 18000.00,
                'annual_price' => 180000.00,
                'trial_days' => 14,
                'max_shops' => 25,
                'max_users' => 150,
                'max_registers' => 60,
                'max_warehouses' => 15,
                'is_active' => 1,
            ]);

        $updateResponse->assertRedirect(route('platform.plans.index'));
        $plan->refresh();
        $this->assertEquals('Enterprise Pro Max', $plan->name);
        $this->assertEquals('18000.00', $plan->monthly_price);

        // 4. Toggle status
        $toggleResponse = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.plans.toggle', $plan));

        $toggleResponse->assertRedirect(route('platform.plans.index'));
        $plan->refresh();
        $this->assertFalse($plan->is_active);
    }

    public function test_billing_admin_can_assign_and_transition_tenant_subscriptions(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('billing-administrator');

        $tenant = Tenant::factory()->create();
        $plan = Plan::create([
            'code' => 'growth-plan',
            'name' => 'Growth Plan',
            'currency' => 'KES',
            'monthly_price' => 5000.00,
            'annual_price' => 50000.00,
            'trial_days' => 14,
            'max_shops' => 3,
            'max_users' => 10,
            'max_registers' => 5,
            'max_warehouses' => 2,
            'is_active' => true,
        ]);

        // 1. Assign plan to tenant
        $assignResponse = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.subscription.assign', $tenant), [
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'status' => 'trial',
            ]);

        $assignResponse->assertRedirect(route('platform.tenants.show', $tenant));

        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals('trial', $subscription->status);
        $this->assertEquals('monthly', $subscription->billing_cycle);
        $this->assertNotNull($subscription->trial_ends_at);

        // Verify audit log for assignment
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'subscription.create',
            'target_type' => Subscription::class,
            'target_id' => (string) $subscription->id,
            'actor_id' => $user->id,
        ]);

        // 2. Transition state to active
        $transitionResponse = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.subscription.transition', [$tenant, $subscription]), [
                'status' => 'active',
                'reason' => 'First invoice paid via M-Pesa',
            ]);

        $transitionResponse->assertRedirect(route('platform.tenants.show', $tenant));
        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);

        // 3. Transition to grace_period
        $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.subscription.transition', [$tenant, $subscription]), [
                'status' => 'grace_period',
                'reason' => 'Renewal payment delayed',
            ]);

        $subscription->refresh();
        $this->assertEquals('grace_period', $subscription->status);
        $this->assertNotNull($subscription->grace_ends_at);
        $this->assertTrue($subscription->isInGracePeriod());

        // 4. Transition to suspended
        $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.subscription.transition', [$tenant, $subscription]), [
                'status' => 'suspended',
                'reason' => 'Grace period expired without settlement',
            ]);

        $subscription->refresh();
        $this->assertEquals('suspended', $subscription->status);
    }

    public function test_inactive_plan_cannot_be_assigned(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('billing-administrator');

        $tenant = Tenant::factory()->create();
        $inactivePlan = Plan::create([
            'code' => 'sunset-plan',
            'name' => 'Sunset Plan',
            'currency' => 'KES',
            'monthly_price' => 1000.00,
            'annual_price' => 10000.00,
            'trial_days' => 0,
            'max_shops' => 1,
            'max_users' => 1,
            'max_registers' => 1,
            'max_warehouses' => 0,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.subscription.assign', $tenant), [
                'plan_id' => $inactivePlan->id,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $inactivePlan->id,
        ]);
    }
}
