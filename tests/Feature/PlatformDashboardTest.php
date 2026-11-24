<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformApproval;
use App\Models\PlatformBackupRun;
use App\Models\PlatformDataProtectionRequest;
use App\Models\PlatformRole;
use App\Models\PlatformSecurityIncident;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use Database\Seeders\ModuleCatalogueSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
        $this->seed(ModuleCatalogueSeeder::class);
    }

    private function createAdmin(string $roleSlug = 'platform-operations-administrator'): array
    {
        $user = PlatformUser::factory()->create([
            'status' => 'active',
            'mfa_confirmed_at' => now(),
        ]);

        $role = PlatformRole::where('slug', $roleSlug)->firstOrFail();
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

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        $response = $this->get(route('platform.dashboard'));
        $response->assertRedirect(route('platform.login'));
    }

    public function test_user_without_mfa_is_redirected_to_mfa_setup(): void
    {
        $user = PlatformUser::factory()->create([
            'status' => 'active',
            'mfa_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user, 'platform')
            ->withSession(['platform_mfa_pending' => true])
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.mfa.setup'));
    }

    public function test_user_without_dashboard_permission_is_forbidden(): void
    {
        $user = PlatformUser::factory()->create([
            'status' => 'active',
            'mfa_confirmed_at' => now(),
        ]);

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
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $response->assertForbidden();
    }

    public function test_authorized_platform_user_can_view_integrated_dashboard(): void
    {
        [$admin, $sessionId] = $this->createAdmin('platform-operations-administrator');

        // Seed sample tenant and plan
        Plan::create([
            'name' => 'Growth Plan',
            'code' => 'GROWTH',
            'monthly_price' => 5000,
            'annual_price' => 50000,
            'currency' => 'KES',
            'is_active' => true,
        ]);

        Tenant::create([
            'name' => 'Acme Retail',
            'code' => 'ACME01',
            'slug' => 'acme-retail',
            'owner_name' => 'Alice Admin',
            'owner_email' => 'alice@acme.test',
            'status' => 'active',
            'business_type' => 'retail',
            'industry' => 'general_retail',
        ]);

        // Backup run record
        PlatformBackupRun::create([
            'backup_type' => 'database',
            'status' => 'completed',
            'filename' => 'backups/backup-2026.enc',
            'destination' => 'local_encrypted',
            'size_bytes' => 10240,
            'checksum' => hash('sha256', 'sample-backup-content'),
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Platform Control Plane Dashboard');
        $response->assertSee('Tenants');
        $response->assertSee('Monthly Recurring Revenue');
        $response->assertSee('Subsystems Health');
        $response->assertSee('Live Subsystem Probes');
        $response->assertSee('Backups & Migrations');
        $response->assertSee('Security Centre & Key Governance');
        $response->assertSee('Compliance & eTIMS Console');
        $response->assertSee('BLOCKED-ON-CERTIFICATION');
        $response->assertSee('Plan Distribution');
        $response->assertSee('Top Adopted Modules');
        $response->assertSee('Industry Blueprints');
    }

    public function test_dashboard_displays_needs_attention_alerts(): void
    {
        [$admin, $sessionId] = $this->createAdmin('platform-operations-administrator');

        // Create pending approval
        PlatformApproval::create([
            'action_type' => 'platform.tenant.restore',
            'target_type' => 'Tenant',
            'target_id' => '1',
            'requested_by' => $admin->id,
            'status' => 'pending',
            'reason' => 'Emergency disaster restore test',
            'payload' => ['tenant_id' => 1],
            'expires_at' => now()->addHours(24),
        ]);

        // Create active security incident
        PlatformSecurityIncident::create([
            'incident_number' => 'INC-2026-999',
            'title' => 'Excessive failed platform logins detected',
            'category' => 'authentication',
            'severity' => 'critical',
            'status' => 'investigating',
            'reported_by' => $admin->id,
            'description' => 'Multiple automated login attempts detected on platform guard.',
        ]);

        // Create overdue DPA request
        PlatformDataProtectionRequest::create([
            'request_number' => 'DPA-2026-999',
            'subject_name' => 'John Doe',
            'subject_email' => 'john@test.com',
            'request_type' => 'access_export',
            'status' => 'pending',
            'due_date' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $response->assertOk();
        $response->assertSee('Security Incident Alert');
        $response->assertSee('Statutory Deadline Alert');
        $response->assertSee('Two-Person Rule Signoff Required');
    }
}
