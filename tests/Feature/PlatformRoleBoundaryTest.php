<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePlatformPermission;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('rolesWithoutRoleManagementPermission')]
    public function test_roles_without_role_management_permission_cannot_create_roles(string $roleSlug): void
    {
        [$user, $sessionId] = $this->makePlatformUserWithRole($roleSlug);
        $roleName = 'Denied Role '.str_replace('-', ' ', $roleSlug);

        $response = $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.roles.store'), [
                'name' => $roleName,
                'description' => 'Role creation must be forbidden for this built-in role.',
                'permission_ids' => [],
                'reason' => 'Test direct role creation authorization.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('platform_roles', ['name' => $roleName]);
    }

    public function test_super_administrator_can_create_a_role_with_permissions_and_audit_it(): void
    {
        [$superAdministrator, $sessionId] = $this->makePlatformUserWithRole('super-administrator');
        $permission = PlatformPermission::query()->where('name', 'platform.tenants.view')->firstOrFail();

        $response = $this->actingAs($superAdministrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->from(route('platform.roles.index'))
            ->post(route('platform.roles.store'), [
                'name' => 'Tenant Review Specialist',
                'description' => 'Can review platform tenant metadata.',
                'permission_ids' => [$permission->id],
                'reason' => 'Needed for tenant review operations.',
            ]);

        $response->assertRedirect(route('platform.roles.index'));
        $this->assertDatabaseHas('platform_roles', [
            'name' => 'Tenant Review Specialist',
            'slug' => 'tenant-review-specialist',
            'is_builtin' => false,
        ]);
        $role = PlatformRole::query()->where('slug', 'tenant-review-specialist')->firstOrFail();
        $this->assertDatabaseHas('platform_permission_role', [
            'platform_role_id' => $role->id,
            'platform_permission_id' => $permission->id,
        ]);
        $this->assertDatabaseHas('platform_audit_log', [
            'actor_id' => $superAdministrator->id,
            'action' => 'platform.role.created',
            'target_id' => (string) $role->id,
        ]);
    }

    public function test_role_creation_still_checks_permission_when_route_permission_middleware_is_missing(): void
    {
        [$supportAgent, $sessionId] = $this->makePlatformUserWithRole('support-agent');

        $response = $this->withoutMiddleware(EnsurePlatformPermission::class)
            ->actingAs($supportAgent, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.roles.store'), [
                'name' => 'Route Middleware Bypass Attempt',
                'description' => 'Must remain forbidden without the role permission.',
                'permission_ids' => [],
                'reason' => 'Test action layer permission enforcement.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('platform_roles', ['name' => 'Route Middleware Bypass Attempt']);
    }

    /** @return array<string, array{string}> */
    public static function rolesWithoutRoleManagementPermission(): array
    {
        return [
            'operations administrator' => ['platform-operations-administrator'],
            'billing administrator' => ['billing-administrator'],
            'support agent' => ['support-agent'],
            'compliance eTIMS officer' => ['compliance-etims-officer'],
            'platform auditor' => ['platform-auditor'],
        ];
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

    /** @return array{PlatformUser, string} */
    private function makePlatformUserWithRole(string $roleSlug): array
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
