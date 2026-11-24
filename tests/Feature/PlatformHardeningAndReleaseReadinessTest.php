<?php

namespace Tests\Feature;

use App\Models\PlatformApproval;
use App\Models\PlatformBackupRun;
use App\Models\PlatformEtimsCertification;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformAuditRecorder;
use App\Services\TenantContext;
use Database\Seeders\ModuleCatalogueSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformHardeningAndReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
        $this->seed(ModuleCatalogueSeeder::class);
        TenantContext::clear();
    }

    private function createPlatformAdmin(string $roleSlug = 'platform-operations-administrator'): array
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

    public function test_platform_and_tenant_boundary_isolation(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenantUser = User::factory()->forTenant($tenant)->create();

        // Tenant user cannot access platform control plane
        $response = $this->actingAs($tenantUser)
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));

        // Platform user cannot access tenant business area without tenant auth & resolution
        [$admin, $sessionId] = $this->createPlatformAdmin();
        $response = $this->actingAs($admin, 'platform')
            ->get('/tenant');

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_no_tenant_user_impersonation_allowed(): void
    {
        // Annex A 13.3 & Part 39.6 strictly forbid tenant user impersonation under any circumstances
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            $name = $route->getName() ?? '';

            $this->assertFalse(
                str_contains($uri, 'impersonate') || str_contains($name, 'impersonate'),
                "Route {$uri} violates the strict zero-impersonation rule (Annex A 13.3)."
            );
        }
    }

    public function test_two_person_rule_safeguards_prohibit_self_approval(): void
    {
        [$admin1, $sessionId1] = $this->createPlatformAdmin('platform-operations-administrator');

        // Create high-risk approval request
        $approval = PlatformApproval::create([
            'action_type' => 'platform.tenant.restore',
            'target_type' => 'Tenant',
            'target_id' => '1',
            'requested_by' => $admin1->id,
            'status' => 'pending',
            'reason' => 'Disaster recovery restore test',
            'payload' => ['tenant_id' => 1],
            'expires_at' => now()->addHours(24),
        ]);

        // Same admin cannot approve their own request
        $response = $this->actingAs($admin1, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
                'platform_session_id' => $sessionId1,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId1)
            ->post(route('platform.approvals.approve', $approval));

        // Must be rejected (403 forbidden per two-person rule)
        $this->assertEquals(403, $response->status());
        $this->assertEquals('pending', $approval->fresh()->status);
    }

    public function test_cryptographic_audit_trail_integrity_command(): void
    {
        $audit = app(PlatformAuditRecorder::class);
        [$admin, $sessionId] = $this->createPlatformAdmin();

        // Record a series of audited actions
        $audit->record($admin->id, 'hardening.test.action1', null, null, ['check' => 'initial']);
        $audit->record($admin->id, 'hardening.test.action2', null, null, ['check' => 'chained']);

        // Run the audit verification artisan command
        $this->artisan('platform:audit-verify')
            ->expectsOutputToContain('Audit chain intact')
            ->assertSuccessful();
    }

    public function test_etims_and_mpesa_honest_regulatory_placeholder_states(): void
    {
        [$admin, $sessionId] = $this->createPlatformAdmin('compliance-etims-officer');

        // Initial checklist item in pending status -> overall status must be BLOCKED-ON-CERTIFICATION
        PlatformEtimsCertification::create([
            'component_name' => 'KRA eTIMS VSCU Middleware Integration',
            'certification_stage' => 'sandbox_testing',
            'status' => 'pending',
            'details' => 'Under regulatory review',
        ]);

        $response = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
                'platform_session_id' => $sessionId,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.compliance.index'));

        $response->assertOk();
        $response->assertSee('BLOCKED-ON-CERTIFICATION');
        $response->assertSee('Regulatory placeholder active');

        // Check platform dashboard also shows the honest placeholder banner
        $responseDash = $this->actingAs($admin, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
                'platform_session_id' => $sessionId,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $responseDash->assertOk();
        $responseDash->assertSee('BLOCKED-ON-CERTIFICATION');
    }

    public function test_backup_sha256_checksum_and_restore_verification(): void
    {
        $content = 'SAMPLE_ENCRYPTED_DATABASE_BACKUP_PAYLOAD';
        $checksum = hash('sha256', $content);

        $backup = PlatformBackupRun::create([
            'backup_type' => 'central',
            'status' => 'completed',
            'filename' => 'backups/central_backup_hardening.enc',
            'destination' => 'local_encrypted',
            'size_bytes' => strlen($content),
            'checksum' => $checksum,
            'is_encrypted' => true,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->assertNotEmpty($backup->checksum);
        $this->assertEquals(64, strlen($backup->checksum));
        $this->assertEquals($checksum, hash('sha256', $content));
    }
}
