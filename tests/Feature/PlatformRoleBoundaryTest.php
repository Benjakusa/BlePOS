<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformRoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_officer_sees_user_controls_but_not_invitation_or_role_editor_controls(): void
    {
        [$securityOfficer, $sessionId] = $this->makeSecurityOfficer();

        $response = $this->actingAs($securityOfficer, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.users.index'));

        $response->assertOk()
            ->assertDontSee('Invite platform staff')
            ->assertDontSee(route('platform.roles.index'))
            ->assertSee('Platform staff');
    }

    public function test_security_officer_cannot_send_platform_staff_invitations(): void
    {
        [$securityOfficer, $sessionId] = $this->makeSecurityOfficer();

        $response = $this->actingAs($securityOfficer, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.users.invite'), [
                'email' => 'forbidden-invite@example.test',
                'platform_role_id' => PlatformRole::query()->where('slug', 'support-agent')->value('id'),
                'reason' => 'Invite attempt for authorization check.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('platform_invitations', ['email' => 'forbidden-invite@example.test']);
    }

    public function test_security_officer_can_review_sessions_but_direct_role_editor_requests_are_denied(): void
    {
        [$securityOfficer, $sessionId] = $this->makeSecurityOfficer();
        $authenticated = $this->actingAs($securityOfficer, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);

        $authenticated->get(route('platform.sessions.index'))->assertOk();
        $authenticated->get(route('platform.roles.index'))->assertForbidden();
        $authenticated->post(route('platform.roles.store'), [
            'name' => 'Unauthorized Role',
            'description' => 'A role creation attempt without permission.',
            'permission_ids' => [],
            'reason' => 'Testing direct role mutation authorization.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('platform_roles', ['name' => 'Unauthorized Role']);
    }

    /** @return array{PlatformUser, string} */
    private function makeSecurityOfficer(): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'security-officer')->firstOrFail();
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
