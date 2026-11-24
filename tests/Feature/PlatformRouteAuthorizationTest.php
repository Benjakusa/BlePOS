<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePlatformPermission;
use App\Models\PlatformApproval;
use App\Models\PlatformInvitation;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_platform_control_route_requires_an_explicit_permission(): void
    {
        $protectedPrefixes = [
            'platform.dashboard',
            'platform.audit.',
            'platform.approvals.',
            'platform.security.',
            'platform.roles.',
            'platform.sessions.',
            'platform.users.',
            'platform.tenants.',
            'platform.provisioning.',
            'platform.plans.',
            'platform.modules.',
            'platform.templates.',
            'platform.invoices.',
            'platform.dunning.',
            'platform.support.',
            'platform.announcements.',
            'platform.infrastructure.',
            'platform.compliance.',
            'platform.analytics.',
        ];
        $protectedRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($protectedPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ));

        $this->assertNotEmpty($protectedRoutes);

        foreach ($protectedRoutes as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:platform', $middleware, "{$route->getName()} must use platform authentication.");
            $this->assertContains('platform.mfa', $middleware, "{$route->getName()} must require MFA.");
            $this->assertContains('platform.session', $middleware, "{$route->getName()} must track and validate its session.");
            $this->assertTrue(
                collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:')),
                "{$route->getName()} must require a named permission.",
            );
        }
    }

    public function test_every_platform_control_mutation_requires_step_up_authentication(): void
    {
        $actionPrefixes = [
            'platform.approvals.',
            'platform.security.',
            'platform.roles.',
            'platform.users.',
            'platform.sessions.',
            'platform.tenants.',
            'platform.provisioning.',
            'platform.plans.',
            'platform.modules.',
            'platform.templates.',
            'platform.invoices.',
            'platform.dunning.',
            'platform.support.',
            'platform.announcements.',
            'platform.infrastructure.',
            'platform.compliance.',
            'platform.analytics.',
        ];
        $mutations = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($actionPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ))
            ->filter(fn ($route): bool => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []);

        $this->assertNotEmpty($mutations);

        foreach ($mutations as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:platform', $middleware, "{$route->getName()} must use platform authentication.");
            $this->assertContains('platform.step-up', $middleware, "{$route->getName()} must require step-up authentication.");
            $this->assertTrue(
                collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:')),
                "{$route->getName()} must require a named permission.",
            );
        }
    }

    public function test_every_platform_control_route_uses_a_permission_in_the_catalogue(): void
    {
        $this->seed(PlatformRoleSeeder::class);

        $protectedPrefixes = [
            'platform.dashboard',
            'platform.audit.',
            'platform.approvals.',
            'platform.security.',
            'platform.roles.',
            'platform.sessions.',
            'platform.users.',
            'platform.tenants.',
            'platform.provisioning.',
            'platform.plans.',
            'platform.modules.',
            'platform.templates.',
            'platform.invoices.',
            'platform.dunning.',
            'platform.support.',
            'platform.announcements.',
            'platform.infrastructure.',
            'platform.compliance.',
            'platform.analytics.',
        ];
        $protectedRoutes = collect(Route::getRoutes())
            ->filter(fn ($route): bool => collect($protectedPrefixes)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ));

        $this->assertNotEmpty($protectedRoutes);

        foreach ($protectedRoutes as $route) {
            $permissionMiddleware = collect($route->gatherMiddleware())
                ->first(fn (string $entry): bool => str_starts_with($entry, 'platform.permission:'));

            $this->assertNotNull($permissionMiddleware, "{$route->getName()} must name its required permission.");

            $permission = substr($permissionMiddleware, strlen('platform.permission:'));
            $this->assertTrue(
                PlatformPermission::query()->where('name', $permission)->exists(),
                "Unknown permission for {$route->getName()}.",
            );
        }
    }

    public function test_platform_action_controllers_recheck_permissions_without_route_middleware(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $restrictedUser = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $restrictedRole = PlatformRole::query()->create([
            'name' => 'Restricted Test Role',
            'slug' => 'restricted-test-role',
            'description' => 'No platform permissions.',
            'is_builtin' => false,
        ]);
        $restrictedUser->roles()->attach($restrictedRole->id);
        $targetUser = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $superAdministratorRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $supportRole = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $auditorRole = PlatformRole::query()->where('slug', 'platform-auditor')->firstOrFail();
        $targetUser->roles()->attach($supportRole->id);
        $editableRole = PlatformRole::query()->create([
            'name' => 'Editable Action Guard Role',
            'slug' => 'editable-action-guard-role',
            'description' => 'Role used to test direct authorization.',
            'is_builtin' => false,
        ]);
        $deletableRole = PlatformRole::query()->create([
            'name' => 'Deletable Action Guard Role',
            'slug' => 'deletable-action-guard-role',
            'description' => 'Role used to test direct authorization.',
            'is_builtin' => false,
        ]);
        $invitation = PlatformInvitation::query()->create([
            'email' => 'pending-action-guard@example.test',
            'platform_role_id' => $supportRole->id,
            'invited_by' => $restrictedUser->id,
            'token_hash' => hash('sha256', 'pending-action-guard-token'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
        $approvalForOtherRequester = $this->createPendingApproval($targetUser, $targetUser, $superAdministratorRole);
        $approvalForRestrictedRequester = $this->createPendingApproval($restrictedUser, $targetUser, $superAdministratorRole);
        $targetSession = $this->createTrackedSession($targetUser);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $restrictedUser->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        $this->withoutMiddleware(EnsurePlatformPermission::class)
            ->actingAs($restrictedUser, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);

        foreach ([
            'platform.dashboard',
            'platform.users.index',
            'platform.roles.index',
            'platform.approvals.index',
            'platform.sessions.index',
            'platform.security.events.index',
            'platform.audit.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }

        Mail::fake();
        Notification::fake();

        $this->post(route('platform.users.invite'), [
            'email' => 'unauthorized-direct-action@example.test',
            'platform_role_id' => $restrictedRole->id,
            'reason' => 'Direct action authorization check.',
        ])->assertForbidden();

        $this->post(route('platform.users.invitations.resend', $invitation), [
            'reason' => 'Direct invitation resend authorization check.',
        ])->assertForbidden();
        $this->delete(route('platform.users.invitations.cancel', $invitation), [
            'reason' => 'Direct invitation cancel authorization check.',
        ])->assertForbidden();
        $this->patch(route('platform.users.status', $targetUser), [
            'status' => 'disabled',
            'reason' => 'Direct account status authorization check.',
        ])->assertForbidden();
        $this->post(route('platform.users.mfa-reset', $targetUser), [
            'reason' => 'Direct MFA reset authorization check.',
        ])->assertForbidden();
        $this->post(route('platform.users.password-reset', $targetUser), [
            'reason' => 'Direct password reset authorization check.',
        ])->assertForbidden();
        $this->post(route('platform.users.roles.assign', $targetUser), [
            'platform_role_id' => $auditorRole->id,
            'reason' => 'Direct role assignment authorization check.',
        ])->assertForbidden();
        $this->delete(route('platform.users.roles.remove', [$targetUser, $supportRole]), [
            'reason' => 'Direct role removal authorization check.',
        ])->assertForbidden();
        $this->post(route('platform.roles.store'), [
            'name' => 'Unauthorized Direct Role',
            'description' => 'A role must not be created.',
            'permission_ids' => [],
            'reason' => 'Direct role store authorization check.',
        ])->assertForbidden();
        $this->put(route('platform.roles.update', $editableRole), [
            'name' => 'Changed Action Guard Role',
            'description' => 'A role must not be changed.',
            'permission_ids' => [],
            'reason' => 'Direct role update authorization check.',
        ])->assertForbidden();
        $this->delete(route('platform.roles.destroy', $deletableRole), [
            'reason' => 'Direct role delete authorization check.',
        ])->assertForbidden();
        $this->post(route('platform.approvals.approve', $approvalForOtherRequester))->assertForbidden();
        $this->post(route('platform.approvals.cancel', $approvalForRestrictedRequester), [
            'reason' => 'Direct approval cancel authorization check.',
        ])->assertForbidden();
        $this->delete(route('platform.sessions.destroy', $targetSession))->assertForbidden();
        $this->delete(route('platform.users.sessions.revoke-all', $targetUser))->assertForbidden();
        $this->get(route('platform.audit.export'))->assertForbidden();

        $this->assertDatabaseMissing('platform_invitations', ['email' => 'unauthorized-direct-action@example.test']);
        $this->assertDatabaseHas('platform_invitations', ['id' => $invitation->id, 'status' => 'pending']);
        $this->assertDatabaseHas('platform_users', ['id' => $targetUser->id, 'status' => 'active', 'must_reset_password' => false]);
        $this->assertDatabaseMissing('platform_role_user', [
            'platform_user_id' => $targetUser->id,
            'platform_role_id' => $auditorRole->id,
        ]);
        $this->assertDatabaseHas('platform_role_user', [
            'platform_user_id' => $targetUser->id,
            'platform_role_id' => $supportRole->id,
        ]);
        $this->assertDatabaseMissing('platform_roles', ['name' => 'Unauthorized Direct Role']);
        $this->assertDatabaseHas('platform_roles', ['id' => $editableRole->id, 'name' => 'Editable Action Guard Role']);
        $this->assertDatabaseHas('platform_roles', ['id' => $deletableRole->id, 'name' => 'Deletable Action Guard Role']);
        $this->assertDatabaseHas('platform_approvals', ['id' => $approvalForOtherRequester->id, 'status' => 'pending']);
        $this->assertDatabaseHas('platform_approvals', ['id' => $approvalForRestrictedRequester->id, 'status' => 'pending']);
        $this->assertDatabaseHas('platform_sessions', ['id' => $targetSession->id, 'revoked_at' => null]);
        $this->assertDatabaseMissing('platform_audit_log', ['action' => 'platform.audit.exported']);
    }

    private function createPendingApproval(PlatformUser $requester, PlatformUser $target, PlatformRole $superAdministratorRole): PlatformApproval
    {
        return PlatformApproval::query()->create([
            'action_type' => 'platform.superadmin.grant',
            'target_type' => PlatformUser::class,
            'target_id' => (string) $target->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
            'reason' => 'Grant platform ownership to the on-call administrator.',
            'payload' => ['platform_role_id' => $superAdministratorRole->id],
            'expires_at' => now()->addDay(),
        ]);
    }

    private function createTrackedSession(PlatformUser $user): PlatformSession
    {
        $sessionId = 'session-for-'.$user->id;

        return PlatformSession::query()->create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
    }
}
