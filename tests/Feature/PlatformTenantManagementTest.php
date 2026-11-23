<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\ProvisioningRun;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformTenantManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    private function createPlatformAdmin(string $roleSlug = 'platform-operations-administrator'): array
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

    public function test_platform_operator_can_list_and_filter_tenants(): void
    {
        [$admin, $sessionId] = $this->createPlatformAdmin();

        $activeTenant = Tenant::factory()->create(['name' => 'Active Pharmacy', 'status' => 'active']);
        $suspendedTenant = Tenant::factory()->create(['name' => 'Suspended Grocery', 'status' => 'suspended']);

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.tenants.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('Active Pharmacy');
        $response->assertDontSee('Suspended Grocery');
    }

    public function test_platform_operator_can_view_tenant_details(): void
    {
        [$admin, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['name' => 'Downtown Supermarket']);

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('Downtown Supermarket');
    }

    public function test_user_without_tenant_permission_cannot_view_tenants(): void
    {
        // Support agent has platform.tenants.view, but let's test a user with an empty custom role
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $emptyRole = PlatformRole::create([
            'name' => 'Empty Role',
            'slug' => 'empty-role',
            'description' => 'No permissions',
        ]);
        $user->roles()->attach($emptyRole->id);

        $sessionId = Str::random(40);
        PlatformSession::create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $response = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.tenants.index'));

        $response->assertForbidden();
    }

    public function test_platform_admin_can_suspend_and_reactivate_tenant_with_audit(): void
    {
        [$admin, $sessionId] = $this->createPlatformAdmin('super-administrator');
        $tenant = Tenant::factory()->create(['status' => 'active']);

        // 1. Suspend tenant
        $suspendResponse = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.suspend', $tenant), [
                'reason' => 'Non-payment of subscription dues after grace period expired.',
            ]);

        $suspendResponse->assertRedirect();
        $this->assertEquals('suspended', $tenant->fresh()->status);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.tenants.suspend',
            'target_id' => (string) $tenant->id,
        ]);

        // 2. Reactivate tenant
        $reactivateResponse = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.tenants.reactivate', $tenant), [
                'reason' => 'Outstanding dues cleared and confirmed via M-Pesa.',
            ]);

        $reactivateResponse->assertRedirect();
        $this->assertEquals('active', $tenant->fresh()->status);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.tenants.reactivate',
            'target_id' => (string) $tenant->id,
        ]);
    }

    public function test_platform_operator_can_retry_failed_provisioning_run(): void
    {
        [$admin, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'provisioning_failed']);

        $run = ProvisioningRun::create([
            'tenant_id' => $tenant->id,
            'run_identifier' => 'PRV-FAILED-01',
            'status' => 'failed',
            'current_step' => 2,
            'total_steps' => 30,
            'payload' => [
                'legal_name' => $tenant->name,
                'code' => $tenant->code,
                'owner_name' => $tenant->owner_name,
                'owner_email' => $tenant->owner_email,
                'domain' => 'retry-success.blepos.co.ke',
            ],
        ]);

        for ($i = 1; $i <= 30; $i++) {
            $run->steps()->create([
                'step_number' => $i,
                'name' => 'Step '.$i,
                'status' => $i === 1 ? 'completed' : ($i === 2 ? 'failed' : 'pending'),
            ]);
        }

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.provisioning.retry', $run), [
                'reason' => 'Domain conflict resolved; triggering retry.',
            ]);

        $response->assertRedirect();
        $this->assertEquals('completed', $run->fresh()->status);
        $this->assertEquals('trial', $tenant->fresh()->status);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.provisioning.retry',
            'target_id' => (string) $run->id,
        ]);
    }
}
