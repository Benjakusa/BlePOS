<?php

namespace Tests\Feature;

use App\Models\PlatformAuditLog;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAuditExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_auditor_can_download_filtered_audit_csv_with_formula_safe_cells(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $auditor = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'platform-auditor')->firstOrFail();
        $auditor->roles()->attach($role->id);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $auditor->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        app(PlatformAuditRecorder::class)->record(
            $auditor->id,
            '=HYPERLINK("https://example.test","open")',
            details: ['source' => 'test'],
        );

        $response = $this->actingAs($auditor, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.audit.export', ['action' => '=HYPERLINK("https://example.test","open")']));

        $response->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('platform.audit.exported', PlatformAuditLog::query()->latest('id')->firstOrFail()->action);
    }

    public function test_platform_auditor_without_export_permission_cannot_download_audit_csv(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $supportAgent = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $supportAgent->roles()->attach($role->id);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $supportAgent->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $response = $this->actingAs($supportAgent, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.audit.export'));

        $response->assertForbidden();
    }
}
