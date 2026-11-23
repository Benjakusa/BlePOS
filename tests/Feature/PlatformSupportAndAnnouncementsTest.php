<?php

namespace Tests\Feature;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\SupportCase;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformSupportAndAnnouncementsTest extends TestCase
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
    private function createPlatformAdmin(string $roleSlug = 'support-agent'): array
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

    public function test_support_agent_can_view_support_cases_index_and_summary(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        SupportCase::query()->create([
            'case_number' => 'CASE-TEST-001',
            'tenant_id' => $tenant->id,
            'requester_name' => 'Jane Mwangi',
            'requester_email' => 'jane@example.test',
            'subject' => 'Printer driver failure on Till 2',
            'description' => 'Thermal printer does not advance paper on sale finalize.',
            'category' => 'hardware_pos',
            'priority' => 'high',
            'status' => 'open',
            'sla_due_at' => now()->addHours(12),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.support.cases.index'));

        $response->assertOk();
        $response->assertSee('Support Centre');
        $response->assertSee('CASE-TEST-001');
        $response->assertSee('Printer driver failure on Till 2');
    }

    public function test_support_agent_can_open_support_case_and_audit_is_recorded(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        [$agent, $agentSession] = $this->createPlatformAdmin('super-administrator');
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.support.cases.store'), [
                'tenant_id' => $tenant->id,
                'requester_name' => 'Peter Ochieng',
                'requester_email' => 'peter@example.test',
                'subject' => 'eTIMS verification timeout on checkout',
                'description' => 'When submitting sales above 50,000 KES, invoice validation times out.',
                'category' => 'etims_tax',
                'priority' => 'urgent',
                'assigned_agent_id' => $agent->id,
            ]);

        $response->assertRedirect();

        $case = SupportCase::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($case);
        $this->assertSame('urgent', $case->priority);
        $this->assertSame('etims_tax', $case->category);
        $this->assertSame($agent->id, $case->assigned_agent_id);
        $this->assertSame('open', $case->status);

        // Urgent priority SLA must be set to 4 hours per Annex A 13.1
        $this->assertNotNull($case->sla_due_at);
        $this->assertTrue(now()->addHours(3)->lessThan($case->sla_due_at));

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'support.case.create',
            'actor_id' => $user->id,
        ]);
    }

    public function test_support_agent_can_view_case_show_and_add_internal_note(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $case = SupportCase::query()->create([
            'case_number' => 'CASE-TEST-002',
            'tenant_id' => $tenant->id,
            'requester_name' => 'Alice Mutua',
            'requester_email' => 'alice@example.test',
            'subject' => 'User role permissions request',
            'description' => 'Need supervisor pin access for cashier Alice.',
            'category' => 'user_access',
            'priority' => 'low',
            'status' => 'open',
            'sla_due_at' => now()->addHours(72),
        ]);

        $showResponse = $this->platformRequest($user, $sessionId)
            ->get(route('platform.support.cases.show', $case));

        $showResponse->assertOk();
        $showResponse->assertSee('CASE-TEST-002');
        $showResponse->assertSee('Need supervisor pin access for cashier Alice.');

        // Add internal note
        $noteResponse = $this->platformRequest($user, $sessionId)
            ->post(route('platform.support.cases.notes.store', $case), [
                'content' => 'Checked tenant roles. Shop manager has PIN authority.',
                'is_internal' => 1,
            ]);

        $noteResponse->assertRedirectToRoute('platform.support.cases.show', $case);

        $this->assertDatabaseHas('support_case_notes', [
            'support_case_id' => $case->id,
            'content' => 'Checked tenant roles. Shop manager has PIN authority.',
            'is_internal' => true,
            'author_name' => $user->name,
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'support.case.note',
            'actor_id' => $user->id,
        ]);
    }

    public function test_support_agent_can_assign_case_to_agent(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        [$targetAgent, $targetSession] = $this->createPlatformAdmin('super-administrator');
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $case = SupportCase::query()->create([
            'case_number' => 'CASE-TEST-003',
            'tenant_id' => $tenant->id,
            'requester_name' => 'Brian Kariuki',
            'requester_email' => 'brian@example.test',
            'subject' => 'Stock adjustment question',
            'description' => 'How to process damaged stock write-off.',
            'category' => 'inventory',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.support.cases.assign', $case), [
                'agent_id' => $targetAgent->id,
            ]);

        $response->assertRedirectToRoute('platform.support.cases.show', $case);

        $case->refresh();
        $this->assertSame($targetAgent->id, $case->assigned_agent_id);
        $this->assertSame('in_progress', $case->status);

        $this->assertDatabaseHas('support_case_notes', [
            'support_case_id' => $case->id,
            'content' => "Assignment changed to: {$targetAgent->name}",
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'support.case.assign',
            'actor_id' => $user->id,
        ]);
    }

    public function test_support_agent_can_update_status_and_resolution_notes(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        $case = SupportCase::query()->create([
            'case_number' => 'CASE-TEST-004',
            'tenant_id' => $tenant->id,
            'requester_name' => 'Carol Chebet',
            'requester_email' => 'carol@example.test',
            'subject' => 'Incorrect tax group assignment',
            'description' => 'Zero-rated items taxed at 16%.',
            'category' => 'etims_tax',
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.support.cases.status', $case), [
                'status' => 'resolved',
                'resolution_notes' => 'Updated item tax categorization to Exempt / Zero-rated.',
            ]);

        $response->assertRedirectToRoute('platform.support.cases.show', $case);

        $case->refresh();
        $this->assertSame('resolved', $case->status);
        $this->assertSame('Updated item tax categorization to Exempt / Zero-rated.', $case->resolution_notes);
        $this->assertNotNull($case->resolved_at);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'support.case.status',
            'actor_id' => $user->id,
        ]);
    }

    public function test_support_agent_can_view_tenant_assist_and_run_integrity_check(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        // View non-impersonating assist overview
        $overviewResponse = $this->platformRequest($user, $sessionId)
            ->get(route('platform.support.assist.overview', $tenant));

        $overviewResponse->assertOk();
        $overviewResponse->assertSee('No-Impersonation Security Policy');
        $overviewResponse->assertSee($tenant->name);

        // Run integrity check
        $checkResponse = $this->platformRequest($user, $sessionId)
            ->post(route('platform.support.assist.integrity', $tenant));

        $checkResponse->assertRedirectToRoute('platform.support.assist.overview', $tenant);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'support.assist.integrity_check',
            'actor_id' => $user->id,
            'target_id' => (string) $tenant->id,
        ]);
    }

    public function test_support_agent_can_publish_and_archive_announcement(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin();

        // Publish announcement
        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.announcements.store'), [
                'title' => 'Scheduled System Maintenance: Nov 30',
                'content' => 'BlePOS cloud platform will undergo a 15-minute maintenance window on Sunday 02:00 UTC.',
                'priority' => 'warning',
                'target_type' => 'all',
            ]);

        $response->assertRedirectToRoute('platform.announcements.index');

        $this->assertDatabaseHas('platform_announcements', [
            'title' => 'Scheduled System Maintenance: Nov 30',
            'priority' => 'warning',
            'target_type' => 'all',
            'status' => 'published',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.announcement.create',
            'actor_id' => $user->id,
        ]);

        $announcement = PlatformAnnouncement::query()->where('title', 'Scheduled System Maintenance: Nov 30')->firstOrFail();

        // Archive announcement
        $archiveResponse = $this->platformRequest($user, $sessionId)
            ->post(route('platform.announcements.archive', $announcement));

        $archiveResponse->assertRedirectToRoute('platform.announcements.index');

        $announcement->refresh();
        $this->assertSame('archived', $announcement->status);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.announcement.archive',
            'actor_id' => $user->id,
        ]);
    }

    public function test_unauthorized_role_cannot_access_support_cases(): void
    {
        [$user, $sessionId] = $this->createPlatformAdmin('compliance-etims-officer'); // does NOT have platform.support.view

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.support.cases.index'));

        $response->assertForbidden();
    }
}
