<?php

namespace Tests\Feature;

use App\Models\PlatformDataProtectionRequest;
use App\Models\PlatformEtimsCertification;
use App\Models\PlatformLegalDocument;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformComplianceAndEtimsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    private function createOfficer(string $roleSlug = 'compliance-etims-officer'): array
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
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_type' => 'desktop',
            'browser' => 'PHPUnit',
            'platform' => 'Linux',
            'last_activity_at' => now(),
            'logged_in_at' => now(),
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

    public function test_compliance_officer_can_view_compliance_and_etims_console(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        $response = $this->platformRequest($officer, $sessionId)
            ->get(route('platform.compliance.index'));

        $response->assertOk();
        $response->assertSee('Compliance & eTIMS Console');
        $response->assertSee('BLOCKED-ON-CERTIFICATION');
        $response->assertSee('KRA eTIMS Certification Checklist');
        $response->assertSee('Platform Invoicing Core & VAT Calculation');
        $response->assertSee('Virtual Sales Control Unit (VSCU) Connector');
    }

    public function test_compliance_officer_can_update_etims_certification_component(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        // First visit to seed checklist
        $this->platformRequest($officer, $sessionId)->get(route('platform.compliance.index'));

        $item = PlatformEtimsCertification::where('component_name', 'like', '%VSCU%')->firstOrFail();

        $response = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.compliance.etims-cert.update', $item), [
                'status' => 'passed',
                'kra_reference' => 'KRA-CERT-VSCU-APPROVED-2026',
                'details' => 'Virtual SCU protocol validated in sandbox and signed off by KRA testing team.',
            ]);

        $response->assertRedirectToRoute('platform.compliance.index');
        $item->refresh();

        $this->assertEquals('passed', $item->status);
        $this->assertEquals('KRA-CERT-VSCU-APPROVED-2026', $item->kra_reference);
        $this->assertNotNull($item->verified_at);
        $this->assertEquals($officer->id, $item->verified_by_user_id);

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.compliance.etims_cert.updated',
            'target_type' => 'PlatformEtimsCertification',
            'target_id' => (string) $item->id,
        ]);
    }

    public function test_data_protection_request_full_lifecycle(): void
    {
        [$officer, $sessionId] = $this->createOfficer();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        // 1. Create Data Protection Request (DSAR)
        $response = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.compliance.dpa.store'), [
                'subject_name' => 'Wanjiku Mwangi',
                'subject_email' => 'wanjiku@example.test',
                'subject_phone' => '+254700112233',
                'request_type' => 'access_export',
                'tenant_id' => $tenant->id,
                'legal_basis' => 'Kenya DPA 2019 Sec 26',
            ]);

        $dpa = PlatformDataProtectionRequest::firstOrFail();
        $response->assertRedirectToRoute('platform.compliance.dpa.show', $dpa);

        $this->assertStringStartsWith('DPA-', $dpa->request_number);
        $this->assertEquals('pending', $dpa->status);
        $this->assertEquals('Wanjiku Mwangi', $dpa->subject_name);
        $this->assertNotNull($dpa->due_date);

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.compliance.dpa_request.created',
            'target_type' => 'PlatformDataProtectionRequest',
            'target_id' => (string) $dpa->id,
        ]);

        // 2. Transition status to processing
        $statusResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.compliance.dpa.status', $dpa), [
                'status' => 'processing',
            ]);

        $statusResponse->assertRedirectToRoute('platform.compliance.dpa.show', $dpa);
        $dpa->refresh();
        $this->assertEquals('processing', $dpa->status);

        // 3. Complete request with resolution summary
        $completeResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.compliance.dpa.status', $dpa), [
                'status' => 'completed',
                'resolution_summary' => 'Encrypted personal data archive generated and delivered via secure portal.',
            ]);

        $completeResponse->assertRedirectToRoute('platform.compliance.dpa.show', $dpa);
        $dpa->refresh();
        $this->assertEquals('completed', $dpa->status);
        $this->assertNotNull($dpa->completed_at);
        $this->assertEquals('Encrypted personal data archive generated and delivered via secure portal.', $dpa->resolution_summary);
    }

    public function test_legal_document_versioning_and_publishing(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        // 1. View legal documents index
        $indexResponse = $this->platformRequest($officer, $sessionId)
            ->get(route('platform.compliance.legal.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('BlePOS Master SaaS Agreement');
        $indexResponse->assertSee('Kenya Data Protection Act 2019');

        // 2. Publish new version of privacy policy
        $publishResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.compliance.legal.store'), [
                'slug' => 'privacy-policy',
                'title' => 'BlePOS Privacy Policy (Kenya DPA 2019 & GDPR Update)',
                'version' => '1.1',
                'content' => 'Updated privacy policy content regarding automated data minimization and cross-border transfer protections.',
                'changelog' => 'Added explicit cross-border transfer safeguards and 30-day response statutory notices.',
                'effective_from' => now()->format('Y-m-d'),
            ]);

        $newDoc = PlatformLegalDocument::where('version', '1.1')->firstOrFail();
        $publishResponse->assertRedirectToRoute('platform.compliance.legal.show', $newDoc);

        $this->assertEquals('privacy-policy', $newDoc->slug);
        $this->assertEquals('1.1', $newDoc->version);
        $this->assertEquals($officer->id, $newDoc->created_by_user_id);

        // 3. View document details
        $showResponse = $this->platformRequest($officer, $sessionId)
            ->get(route('platform.compliance.legal.show', $newDoc));

        $showResponse->assertOk();
        $showResponse->assertSee('BlePOS Privacy Policy (Kenya DPA 2019 & GDPR Update)');
        $showResponse->assertSee('v1.1');
        $showResponse->assertSee('Added explicit cross-border transfer safeguards');

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.compliance.legal_doc.published',
            'target_type' => 'PlatformLegalDocument',
            'target_id' => (string) $newDoc->id,
        ]);
    }

    public function test_unprivileged_platform_user_denied_compliance_access(): void
    {
        [$supportAgent, $sessionId] = $this->createOfficer('support-agent');

        $response = $this->platformRequest($supportAgent, $sessionId)
            ->get(route('platform.compliance.index'));

        $response->assertForbidden();

        $dpaResponse = $this->platformRequest($supportAgent, $sessionId)
            ->get(route('platform.compliance.dpa.index'));

        $dpaResponse->assertForbidden();

        $legalResponse = $this->platformRequest($supportAgent, $sessionId)
            ->get(route('platform.compliance.legal.index'));

        $legalResponse->assertForbidden();
    }
}
