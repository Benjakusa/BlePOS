<?php

namespace Tests\Feature;

use App\Models\PlatformIpRule;
use App\Models\PlatformRole;
use App\Models\PlatformSecurityIncident;
use App\Models\PlatformSecurityKey;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformSecurityCentreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    private function createOfficer(string $roleSlug = 'security-officer'): array
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

    public function test_security_officer_can_view_security_centre_dashboard(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        $response = $this->platformRequest($officer, $sessionId)
            ->get(route('platform.security.index'));

        $response->assertOk();
        $response->assertSee('Security Centre');
        $response->assertSee('Active Incidents');
        $response->assertSee('Critical Events');
        $response->assertSee('Blocked IPs');
        $response->assertSee('Cryptographic Key Inventory');
    }

    public function test_security_incident_lifecycle_from_creation_to_resolution(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        // 1. Create incident
        $response = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.incidents.store'), [
                'title' => 'Suspicious credential stuffing from external botnet',
                'category' => 'credential_stuffing',
                'severity' => 'critical',
                'description' => 'Over 500 failed login attempts across multiple tenant accounts within 5 minutes.',
                'containment_steps' => 'Temporary IP range block applied; progressive delay triggered.',
                'initial_note' => 'Identified botnet user agent signature.',
            ]);

        $incident = PlatformSecurityIncident::firstOrFail();
        $response->assertRedirectToRoute('platform.security.incidents.show', $incident);

        $this->assertEquals('critical', $incident->severity);
        $this->assertEquals('new', $incident->status);
        $this->assertStringStartsWith('INC-', $incident->incident_number);
        $this->assertCount(1, $incident->notes);

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.security.incident.created',
            'target_type' => 'PlatformSecurityIncident',
            'target_id' => (string) $incident->id,
        ]);

        // 2. Add timeline note
        $noteResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.incidents.notes', $incident), [
                'note' => 'Blocked 3 offending subnet ranges.',
                'is_containment_action' => 1,
            ]);

        $noteResponse->assertRedirectToRoute('platform.security.incidents.show', $incident);
        $incident->refresh();
        $this->assertCount(2, $incident->notes);
        $this->assertTrue($incident->notes->last()->is_containment_action);

        // 3. Update status to resolved
        $statusResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.incidents.status', $incident), [
                'status' => 'resolved',
                'notes' => 'Threat mitigated; all botnet IPs blocked and tenant credentials protected.',
            ]);

        $statusResponse->assertRedirectToRoute('platform.security.incidents.show', $incident);
        $incident->refresh();
        $this->assertEquals('resolved', $incident->status);
        $this->assertNotNull($incident->resolved_at);
        $this->assertEquals('Threat mitigated; all botnet IPs blocked and tenant credentials protected.', $incident->resolution_notes);
    }

    public function test_dynamic_ip_rule_creation_enforcement_and_toggle(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        // 1. Create a dynamic denylist rule
        $response = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.ip-rules.store'), [
                'ip_or_cidr' => '198.51.100.99',
                'rule_type' => 'denylist',
                'scope' => 'platform_console',
                'reason' => 'Observed brute-force probing',
                'duration_minutes' => 60,
            ]);

        $response->assertRedirectToRoute('platform.security.ip-rules.index');
        $rule = PlatformIpRule::firstOrFail();
        $this->assertEquals('198.51.100.99', $rule->ip_or_cidr);
        $this->assertEquals('denylist', $rule->rule_type);
        $this->assertTrue($rule->is_active);

        // 2. Incoming request from that IP is rejected by middleware with 403
        $blockedResponse = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
            ->get(route('platform.login'));

        $blockedResponse->assertForbidden();

        // Check security event
        $this->assertDatabaseHas('platform_security_events', [
            'event_type' => 'platform.network.ip_denied',
            'severity' => 'critical',
            'ip_address' => '198.51.100.99',
        ]);

        // 3. Toggle rule to inactive from authorized officer IP
        $toggleResponse = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $toggleResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.ip-rules.toggle', $rule));

        $toggleResponse->assertRedirectToRoute('platform.security.ip-rules.index');
        $rule->refresh();
        $this->assertFalse($rule->is_active);

        // 4. Now the client IP is no longer blocked by the denylist rule
        $allowedResponse = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.99'])
            ->get(route('platform.login'));

        $allowedResponse->assertOk();
    }

    public function test_cryptographic_key_governance_and_rotation(): void
    {
        [$officer, $sessionId] = $this->createOfficer();

        // 1. View keys console
        $response = $this->platformRequest($officer, $sessionId)
            ->get(route('platform.security.keys.index'));

        $response->assertOk();
        $response->assertSee('Platform Master Key');
        $response->assertSee('Tenant Data Envelope Key');

        $key = PlatformSecurityKey::where('key_id', 'pmk-master-v1')->firstOrFail();
        $oldVersion = $key->version;
        $oldFingerprint = $key->fingerprint;

        // 2. Rotate key
        $rotateResponse = $this->platformRequest($officer, $sessionId)
            ->post(route('platform.security.keys.rotate', $key), [
                'reason' => 'Scheduled quarterly cryptographic root key rotation',
            ]);

        $rotateResponse->assertRedirectToRoute('platform.security.keys.index');
        $key->refresh();

        $this->assertEquals($oldVersion + 1, $key->version);
        $this->assertNotEquals($oldFingerprint, $key->fingerprint);
        $this->assertNotNull($key->last_rotated_at);
        $this->assertNotNull($key->next_rotation_due_at);

        // Verify audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.security.key.rotated',
            'target_type' => 'PlatformSecurityKey',
            'target_id' => (string) $key->id,
        ]);
    }

    public function test_unprivileged_platform_user_denied_security_centre_access(): void
    {
        [$billingAdmin, $sessionId] = $this->createOfficer('billing-administrator');

        $response = $this->platformRequest($billingAdmin, $sessionId)
            ->get(route('platform.security.index'));

        $response->assertForbidden();

        $incidentResponse = $this->platformRequest($billingAdmin, $sessionId)
            ->get(route('platform.security.incidents.index'));

        $incidentResponse->assertForbidden();

        $ipRulesResponse = $this->platformRequest($billingAdmin, $sessionId)
            ->get(route('platform.security.ip-rules.index'));

        $ipRulesResponse->assertForbidden();

        $keysResponse = $this->platformRequest($billingAdmin, $sessionId)
            ->get(route('platform.security.keys.index'));

        $keysResponse->assertForbidden();
    }
}
