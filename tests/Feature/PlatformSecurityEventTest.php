<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSecurityEvent;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSecurityEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_platform_auditor_can_filter_and_review_security_events(): void
    {
        [$auditor, $sessionId] = $this->makePlatformUser('platform-auditor');
        PlatformSecurityEvent::query()->create([
            'platform_user_id' => $auditor->id,
            'event_type' => 'platform.mfa.challenge_failed',
            'severity' => 'warning',
            'ip_address' => '198.51.100.20',
            'user_agent' => 'Test Browser',
            'metadata' => ['attempt' => 2],
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($auditor, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.security.events.index', [
                'search' => '198.51.100.20',
                'severity' => 'warning',
            ]));

        $response->assertOk()
            ->assertSee('platform.mfa.challenge_failed')
            ->assertSee('198.51.100.20')
            ->assertSee('Test Browser')
            ->assertSee('&quot;attempt&quot;', false);
    }

    public function test_a_platform_user_without_security_view_permission_gets_forbidden(): void
    {
        [$supportAgent, $sessionId] = $this->makePlatformUser('support-agent');

        $response = $this->actingAs($supportAgent, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.security.events.index'));

        $response->assertForbidden();
    }

    /** @return array{PlatformUser, string} */
    private function makePlatformUser(string $roleSlug): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role->id);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$user, $sessionId];
    }
}
