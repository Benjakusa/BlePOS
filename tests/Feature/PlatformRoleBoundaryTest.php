<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePlatformPermission;
use App\Models\PlatformApproval;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_security_officer_cannot_assign_roles_or_require_password_resets_when_permission_middleware_is_missing(): void
    {
        [$securityOfficer, $sessionId] = $this->makePlatformUserWithRole('security-officer');
        $supportRole = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $auditorRole = PlatformRole::query()->where('slug', 'platform-auditor')->firstOrFail();
        $targetUser = PlatformUser::factory()->create();
        $targetUser->roles()->attach($supportRole->id);

        $authenticated = $this->withoutMiddleware(EnsurePlatformPermission::class)
            ->actingAs($securityOfficer, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);

        $authenticated->get(route('platform.roles.index'))->assertForbidden();
        $authenticated->post(route('platform.users.roles.assign', $targetUser), [
            'platform_role_id' => $auditorRole->id,
            'reason' => 'Testing role assignment beyond security permissions.',
        ])->assertForbidden();
        $authenticated->delete(route('platform.users.roles.remove', [$targetUser, $supportRole]), [
            'reason' => 'Testing role removal beyond security permissions.',
        ])->assertForbidden();
        $authenticated->post(route('platform.users.password-reset', $targetUser), [
            'reason' => 'Testing password reset beyond security permissions.',
        ])->assertForbidden();

        $this->assertDatabaseHas('platform_role_user', [
            'platform_user_id' => $targetUser->id,
            'platform_role_id' => $supportRole->id,
        ]);
        $this->assertDatabaseMissing('platform_role_user', [
            'platform_user_id' => $targetUser->id,
            'platform_role_id' => $auditorRole->id,
        ]);
        $this->assertFalse($targetUser->fresh()->must_reset_password);
    }

    public function test_platform_auditor_cannot_mutate_users_roles_approvals_or_sessions_when_permission_middleware_is_missing(): void
    {
        [$auditor, $sessionId] = $this->makePlatformUserWithRole('platform-auditor');
        $targetUser = PlatformUser::factory()->create();
        $supportRole = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $targetUser->roles()->attach($supportRole->id);
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $targetUser->id,
            'session_hash' => hash('sha256', 'auditor-boundary-session'),
            'session_identifier' => 'auditor-boundary-session',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        $approval = PlatformApproval::query()->create([
            'action_type' => 'platform.superadmin.grant',
            'target_type' => PlatformUser::class,
            'target_id' => (string) $targetUser->id,
            'requested_by' => $targetUser->id,
            'status' => 'pending',
            'reason' => 'Grant administrative access after independent review.',
            'payload' => ['platform_role_id' => PlatformRole::query()->where('slug', 'super-administrator')->value('id')],
            'expires_at' => now()->addDay(),
        ]);

        $authenticated = $this->withoutMiddleware(EnsurePlatformPermission::class)
            ->actingAs($auditor, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);

        $authenticated->patch(route('platform.users.status', $targetUser), [
            'status' => 'disabled',
            'reason' => 'Attempted account status change from a read-only account.',
        ])->assertForbidden();
        $authenticated->delete(route('platform.roles.destroy', $supportRole), [
            'reason' => 'Attempted role deletion from a read-only account.',
        ])->assertForbidden();
        $authenticated->post(route('platform.approvals.cancel', $approval), [
            'reason' => 'Attempted approval cancellation from a read-only account.',
        ])->assertForbidden();
        $authenticated->delete(route('platform.sessions.destroy', $targetSession))->assertForbidden();

        $this->assertDatabaseHas('platform_users', ['id' => $targetUser->id, 'status' => 'active']);
        $this->assertDatabaseHas('platform_roles', ['id' => $supportRole->id, 'slug' => 'support-agent']);
        $this->assertDatabaseHas('platform_approvals', ['id' => $approval->id, 'status' => 'pending']);
        $this->assertDatabaseHas('platform_sessions', ['id' => $targetSession->id, 'revoked_at' => null]);
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

    public function test_builtin_roles_can_only_open_their_authorized_platform_screens(): void
    {
        $this->seed(PlatformRoleSeeder::class);

        $screenAccess = [
            'platform.dashboard' => [
                'platform-operations-administrator',
                'billing-administrator',
                'support-agent',
                'compliance-etims-officer',
                'platform-auditor',
                'security-officer',
                'super-administrator',
            ],
            'platform.users.index' => ['security-officer', 'super-administrator'],
            'platform.roles.index' => ['super-administrator'],
            'platform.approvals.index' => ['security-officer', 'super-administrator'],
            'platform.sessions.index' => ['security-officer', 'super-administrator'],
            'platform.security.events.index' => ['platform-auditor', 'security-officer', 'super-administrator'],
            'platform.audit.index' => ['platform-auditor', 'security-officer', 'super-administrator'],
            'platform.audit.export' => ['platform-auditor', 'super-administrator'],
        ];
        $roleSlugs = PlatformRole::query()->where('is_builtin', true)->orderBy('slug')->pluck('slug');

        foreach ($roleSlugs as $roleSlug) {
            [$user, $sessionId] = $this->makePlatformUserWithRole($roleSlug);
            $authenticated = $this->actingAs($user, 'platform')
                ->withSession(['platform_mfa_verified_at' => now()->timestamp])
                ->withCookie(config('session.platform.cookie'), $sessionId);

            foreach ($screenAccess as $routeName => $allowedRoles) {
                $response = $authenticated->get(route($routeName));

                if (in_array($roleSlug, $allowedRoles, true)) {
                    $response->assertOk("{$roleSlug} should access {$routeName}.");
                } else {
                    $response->assertForbidden("{$roleSlug} should be denied from {$routeName}.");
                }
            }
        }
    }

    public function test_super_administrator_can_create_a_role_with_each_catalogued_permission_and_audit_it(): void
    {
        [$superAdministrator, $sessionId] = $this->makePlatformUserWithRole('super-administrator');
        $permissionIds = PlatformPermission::query()->orderBy('id')->pluck('id')->all();

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
                'permission_ids' => $permissionIds,
                'reason' => 'Needed for tenant review operations.',
            ]);

        $response->assertRedirect(route('platform.roles.index'));
        $this->assertDatabaseHas('platform_roles', [
            'name' => 'Tenant Review Specialist',
            'slug' => 'tenant-review-specialist',
            'is_builtin' => false,
        ]);
        $role = PlatformRole::query()->where('slug', 'tenant-review-specialist')->firstOrFail();
        $this->assertSame(
            $permissionIds,
            $role->permissions()->orderBy('platform_permissions.id')->pluck('platform_permissions.id')->all(),
        );
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
        $sessionId = Str::random(40);
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
