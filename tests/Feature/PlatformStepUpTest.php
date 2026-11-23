<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformStepUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_up_requires_the_current_password_and_totp_and_records_success(): void
    {
        $this->travelTo('1970-01-01 00:00:59');
        [$administrator, $sessionId] = $this->makeAdministrator();
        $this->actingAs($administrator, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.step-up.store'), [
                'password' => 'Test-password-123!',
                'code' => '287082',
            ])
            ->assertRedirect(route('platform.dashboard'))
            ->assertSessionHas('platform_step_up_at', now()->timestamp);

        $this->assertDatabaseHas('platform_audit_log', [
            'actor_id' => $administrator->id,
            'action' => 'platform.step_up.succeeded',
        ]);
    }

    public function test_invalid_password_or_totp_does_not_grant_step_up(): void
    {
        $this->travelTo('1970-01-01 00:00:59');
        [$administrator, $sessionId] = $this->makeAdministrator();
        $this->actingAs($administrator, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->from(route('platform.step-up'))
            ->post(route('platform.step-up.store'), [
                'password' => 'incorrect-password',
                'code' => '287082',
            ])
            ->assertRedirect(route('platform.step-up'))
            ->assertSessionHasErrors('password');

        $this->assertNull(session('platform_step_up_at'));

        $this->post(route('platform.step-up.store'), [
            'password' => 'Test-password-123!',
            'code' => '000000',
        ])->assertSessionHasErrors('password');

        $this->assertNull(session('platform_step_up_at'));
        $this->assertDatabaseMissing('platform_audit_log', ['action' => 'platform.step_up.succeeded']);
    }

    public function test_expired_step_up_is_rejected_before_a_sensitive_role_mutation(): void
    {
        [$administrator, $sessionId] = $this->makeAdministrator();
        $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->subMinutes(6)->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.roles.store'), [
                'name' => 'Step Up Expiry Probe',
                'description' => 'Must not be created after step-up expires.',
                'permission_ids' => [],
                'reason' => 'Verify expired step-up blocks this action.',
            ])
            ->assertRedirect(route('platform.step-up'));

        $this->assertDatabaseMissing('platform_roles', ['name' => 'Step Up Expiry Probe']);
    }

    /** @return array{PlatformUser, string} */
    private function makeAdministrator(): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $administrator = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [],
        ]);
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
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
}
