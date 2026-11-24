<?php

namespace Tests\Feature;

use App\Models\PlatformBackupRun;
use App\Models\PlatformRestoreRequest;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformInfrastructureAndBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    /**
     * Helper to create a platform user with a specific role and session.
     *
     * @return array{0: PlatformUser, 1: string}
     */
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

    private function platformRequest(PlatformUser $user, string $sessionId)
    {
        return $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);
    }

    public function test_operations_admin_can_view_infrastructure_dashboard_and_probes(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.infrastructure.index'));

        $response->assertOk();
        $response->assertSee('Infrastructure');
        $response->assertSee('database');
        $response->assertSee('cache');
        $response->assertSee('storage');
        $response->assertSee('queues');
    }

    public function test_operations_admin_can_refresh_probes_and_audit_is_recorded(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.infrastructure.probes'));

        $response->assertRedirectToRoute('platform.infrastructure.index');

        $this->assertDatabaseHas('platform_health_probes', [
            'subsystem' => 'database',
            'status' => 'healthy',
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'infrastructure.probes.run',
            'actor_id' => $user->id,
        ]);
    }

    public function test_operations_admin_can_view_queues_and_retry_failed_job(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SampleJob']),
            'exception' => 'Connection timeout to email server',
            'failed_at' => now(),
        ]);

        $viewResponse = $this->platformRequest($user, $sessionId)
            ->get(route('platform.infrastructure.queues'));

        $viewResponse->assertOk();
        $viewResponse->assertSee('Queues');
        $viewResponse->assertSee('Connection timeout to email server');

        $jobId = DB::table('failed_jobs')->value('id');

        $retryResponse = $this->platformRequest($user, $sessionId)
            ->post(route('platform.infrastructure.queues.retry', $jobId));

        $retryResponse->assertRedirectToRoute('platform.infrastructure.queues');

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'infrastructure.queues.retry',
            'actor_id' => $user->id,
        ]);
    }

    public function test_operations_admin_can_view_migrations_status(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.infrastructure.migrations'));

        $response->assertOk();
        $response->assertSee('Database Migrations');
        $response->assertSee('MIGRATED');
    }

    public function test_operations_admin_can_trigger_encrypted_backup_and_audit(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.infrastructure.backups.trigger'), [
                'backup_type' => 'tenant_database',
                'tenant_id' => $tenant->id,
            ]);

        $response->assertRedirectToRoute('platform.infrastructure.backups');

        $this->assertDatabaseHas('platform_backup_runs', [
            'backup_type' => 'tenant_database',
            'tenant_id' => $tenant->id,
            'is_encrypted' => true,
            'destination' => 'local_encrypted',
            'status' => 'verified',
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'infrastructure.backup.trigger',
            'actor_id' => $user->id,
        ]);
    }

    public function test_two_person_rule_enforced_on_tenant_restore(): void
    {
        [$requester, $reqSession] = $this->createPlatformAdmin();
        [$approver, $appSession] = $this->createPlatformAdmin('super-administrator');
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $backup = PlatformBackupRun::query()->create([
            'backup_type' => 'tenant_database',
            'tenant_id' => $tenant->id,
            'status' => 'verified',
            'filename' => 'tenant_backup_snapshot.enc.sql',
            'size_bytes' => 2048000,
            'checksum' => hash('sha256', 'tenant_backup_snapshot'),
            'is_encrypted' => true,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour(),
        ]);

        // Step 1: Requester submits restore request
        $createResponse = $this->platformRequest($requester, $reqSession)
            ->post(route('platform.infrastructure.restores.store'), [
                'tenant_id' => $tenant->id,
                'platform_backup_run_id' => $backup->id,
                'reason' => 'Recovery test after simulated schema alteration.',
            ]);

        $createResponse->assertRedirectToRoute('platform.infrastructure.restores');

        $restore = PlatformRestoreRequest::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame('requested', $restore->status);
        $this->assertSame($requester->id, $restore->requested_by);

        // Step 2: Requester attempts to approve own restore request -> VIOLATES Two-Person Rule!
        $selfApprovalResponse = $this->platformRequest($requester, $reqSession)
            ->post(route('platform.infrastructure.restores.approve', $restore));

        $selfApprovalResponse->assertRedirect();
        $selfApprovalResponse->assertSessionHasErrors('approver');

        $restore->refresh();
        $this->assertSame('requested', $restore->status);
        $this->assertNull($restore->approved_by);

        // Step 3: Second administrator approves restore request -> SUCCESS
        $validApprovalResponse = $this->platformRequest($approver, $appSession)
            ->post(route('platform.infrastructure.restores.approve', $restore), [
                'verification_notes' => 'Tested in isolated sandbox. Verified all shop registers match.',
            ]);

        $validApprovalResponse->assertRedirectToRoute('platform.infrastructure.restores');

        $restore->refresh();
        $this->assertSame('production_completed', $restore->status);
        $this->assertSame($approver->id, $restore->approved_by);
        $this->assertNotNull($restore->executed_at);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'infrastructure.restore.approve',
            'actor_id' => $approver->id,
        ]);
    }

    public function test_unprivileged_role_cannot_access_infrastructure(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('support-agent'); // does NOT have platform.infrastructure.view

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.infrastructure.index'));

        $response->assertForbidden();
    }
}
