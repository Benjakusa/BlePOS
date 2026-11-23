<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformPasswordResetNotification;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformRequirePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_forces_a_password_reset_and_revokes_the_users_sessions(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $administrator = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $target = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $administrator->roles()->attach($role->id);
        $adminSessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $administrator->id,
            'session_hash' => hash('sha256', $adminSessionId),
            'session_identifier' => $adminSessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'target-before-password-reset'),
            'session_identifier' => 'target-before-password-reset',
            'logged_in_at' => now()->subHour(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(7),
        ]);
        Notification::fake();

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $adminSessionId)
            ->post(route('platform.users.password-reset', $target), [
                'reason' => 'The user reported possible credential exposure.',
            ]);

        $response->assertRedirect();
        $this->assertTrue($target->fresh()->must_reset_password);
        $this->assertNotNull($targetSession->fresh()->revoked_at);
        Notification::assertSentTo($target, PlatformPasswordResetNotification::class);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.user.password_reset_required']);
    }
}
