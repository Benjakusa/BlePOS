<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PlatformUserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabling_a_user_audits_the_change_and_revokes_all_their_sessions(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create();
        $targetSession = $this->makeTargetSession($target);

        $this->statusRequest($administrator, $sessionId, $target, 'disabled')
            ->assertRedirect();

        $this->assertSame('disabled', $target->fresh()->status);
        $this->assertNotNull($targetSession->fresh()->revoked_at);
        $this->assertDatabaseHas('platform_audit_log', [
            'actor_id' => $administrator->id,
            'action' => 'platform.user.status_changed',
            'target_id' => (string) $target->id,
        ]);
    }

    public function test_reactivation_clears_login_lock_state(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create([
            'status' => 'locked',
            'failed_login_attempts' => 10,
            'locked_until' => now()->addMinutes(20),
        ]);

        $this->statusRequest($administrator, $sessionId, $target, 'active')
            ->assertRedirect();

        $this->assertSame('active', $target->fresh()->status);
        $this->assertSame(0, $target->fresh()->failed_login_attempts);
        $this->assertNull($target->fresh()->locked_until);
    }

    public function test_an_administrator_cannot_change_their_own_status(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();

        $this->statusRequest($administrator, $sessionId, $administrator, 'disabled')
            ->assertUnprocessable();

        $this->assertSame('active', $administrator->fresh()->status);
    }

    public function test_the_last_active_super_administrator_cannot_be_disabled(): void
    {
        [$securityOfficer, $sessionId] = $this->makeAuthenticatedAdministrator('security-officer');
        $target = PlatformUser::factory()->create();
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $target->roles()->attach($superAdminRole->id);

        $this->statusRequest($securityOfficer, $sessionId, $target, 'disabled')
            ->assertStatus(409);

        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_status_and_session_revocation_roll_back_if_audit_recording_fails(): void
    {
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $target = PlatformUser::factory()->create();
        $targetSession = $this->makeTargetSession($target);
        $this->mock(PlatformAuditRecorder::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('Audit store unavailable.'));
        });

        $this->statusRequest($administrator, $sessionId, $target, 'disabled')
            ->assertServerError();

        $this->assertSame('active', $target->fresh()->status);
        $this->assertNull($targetSession->fresh()->revoked_at);
    }

    private function statusRequest(PlatformUser $administrator, string $sessionId, PlatformUser $target, string $status)
    {
        return $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->patch(route('platform.users.status', $target), [
                'status' => $status,
                'reason' => 'Status changed for a verified security request.',
            ]);
    }

    /** @return array{PlatformUser, string} */
    private function makeAuthenticatedAdministrator(string $roleSlug = 'super-administrator'): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $administrator = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', $roleSlug)->firstOrFail();
        $administrator->roles()->attach($role->id);
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

    private function makeTargetSession(PlatformUser $target): PlatformSession
    {
        return PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'target-session-'.$target->id),
            'session_identifier' => 'target-session-'.$target->id,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
    }
}
