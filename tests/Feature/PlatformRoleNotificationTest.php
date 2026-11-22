<?php

namespace Tests\Feature;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformRoleChanged;
use App\Services\PlatformAuditRecorder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

class PlatformRoleNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_a_platform_role_notifies_the_user_and_revokes_their_existing_session(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $supportRole = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'target-session'),
            'session_identifier' => 'target-session',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        Notification::fake();

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.users.roles.assign', $target), [
                'platform_role_id' => $supportRole->id,
                'reason' => 'Assigned for customer support duties.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('platform_role_user', [
            'platform_user_id' => $target->id,
            'platform_role_id' => $supportRole->id,
        ]);
        $this->assertNotNull($targetSession->fresh()->revoked_at);
        Notification::assertSentTo($target, PlatformRoleChanged::class, function (PlatformRoleChanged $notification): bool {
            return $notification->roleName === 'Support Agent'
                && $notification->change === 'assigned to your account'
                && $notification->reason === 'Assigned for customer support duties.';
        });
    }

    public function test_role_permission_edits_notify_assigned_users_and_revoke_their_sessions(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->create([
            'name' => 'Warehouse Staff',
            'slug' => 'warehouse-staff',
            'description' => 'Warehouse access.',
            'is_builtin' => false,
        ]);
        $target->roles()->attach($role->id);
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'warehouse-session'),
            'session_identifier' => 'warehouse-session',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        $permissionId = PlatformPermission::query()->where('name', 'platform.audit.view')->value('id');
        Notification::fake();

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->put(route('platform.roles.update', $role), [
                'name' => 'Warehouse Staff',
                'description' => 'Warehouse access with audit review.',
                'permission_ids' => [$permissionId],
                'reason' => 'Grant audit review for warehouse reconciliation.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('platform_permission_role', [
            'platform_role_id' => $role->id,
            'platform_permission_id' => $permissionId,
        ]);
        $this->assertNotNull($targetSession->fresh()->revoked_at);
        Notification::assertSentTo($target, PlatformRoleChanged::class, function (PlatformRoleChanged $notification): bool {
            return $notification->roleName === 'Warehouse Staff'
                && $notification->change === 'updated'
                && $notification->reason === 'Grant audit review for warehouse reconciliation.';
        });
    }

    public function test_role_permission_change_rolls_back_if_audit_recording_fails(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->create([
            'name' => 'Warehouse Staff',
            'slug' => 'warehouse-staff',
            'description' => 'Original warehouse access.',
            'is_builtin' => false,
        ]);
        $target->roles()->attach($role->id);
        $originalPermission = PlatformPermission::query()->where('name', 'platform.audit.view')->firstOrFail();
        $newPermission = PlatformPermission::query()->where('name', 'platform.audit.export')->firstOrFail();
        $role->permissions()->attach($originalPermission->id);
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'rollback-session'),
            'session_identifier' => 'rollback-session',
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        Notification::fake();
        $this->mock(PlatformAuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('Audit store unavailable.'));
        });

        $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->put(route('platform.roles.update', $role), [
                'name' => 'Warehouse Staff Updated',
                'description' => 'Updated warehouse access.',
                'permission_ids' => [$newPermission->id],
                'reason' => 'Update role permissions after reconciliation.',
            ])
            ->assertServerError();

        $this->assertDatabaseHas('platform_roles', [
            'id' => $role->id,
            'name' => 'Warehouse Staff',
            'description' => 'Original warehouse access.',
        ]);
        $this->assertDatabaseHas('platform_permission_role', [
            'platform_role_id' => $role->id,
            'platform_permission_id' => $originalPermission->id,
        ]);
        $this->assertDatabaseMissing('platform_permission_role', [
            'platform_role_id' => $role->id,
            'platform_permission_id' => $newPermission->id,
        ]);
        $this->assertNull($targetSession->fresh()->revoked_at);
        Notification::assertNothingSent();
    }

    public function test_renaming_a_custom_role_to_an_existing_role_slug_is_rejected(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->create([
            'name' => 'Warehouse Staff',
            'slug' => 'warehouse-staff',
            'description' => 'Warehouse access.',
            'is_builtin' => false,
        ]);

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->put(route('platform.roles.update', $role), [
                'name' => 'Support-Agent',
                'description' => 'Updated role.',
                'permission_ids' => [],
                'reason' => 'Rename role to match support work.',
            ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('platform_roles', ['id' => $role->id, 'slug' => 'warehouse-staff']);
    }

    public function test_role_editor_shows_grouped_permissions_and_a_before_after_diff(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->create([
            'name' => 'Warehouse Staff',
            'slug' => 'warehouse-staff',
            'description' => 'Warehouse access.',
            'is_builtin' => false,
        ]);
        $auditPermission = PlatformPermission::query()->where('name', 'platform.audit.view')->firstOrFail();
        $role->permissions()->attach($auditPermission->id);

        $response = $this->actingAs($administrator, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.roles.index'));

        $response->assertOk()
            ->assertSee('Permission grants')
            ->assertSee('Permission changes')
            ->assertSee('Search permissions')
            ->assertSee('data-initial="1"', false)
            ->assertSee('data-permission-diff', false);
        preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $nonceMatches);
        $this->assertArrayHasKey(1, $nonceMatches);
        $response->assertSee('nonce="'.$nonceMatches[1].'"', false);
    }

    /** @return array{PlatformUser, string} */
    private function makeAuthenticatedAdministrator(): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $administrator = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $administrator->roles()->attach($superAdminRole->id);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $administrator->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$administrator, $sessionId];
    }
}
