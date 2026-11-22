<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlatformSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_idle_platform_session_is_revoked_after_thirty_minutes(): void
    {
        [$administrator, $sessionId, $session] = $this->makePlatformSession(now()->subMinutes(31), now()->addHours(8));

        $response = $this->actingAs($administrator, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));
        $this->assertNotNull($session->fresh()->revoked_at);
        $this->assertGuest('platform');
    }

    public function test_a_platform_session_is_revoked_after_its_eight_hour_absolute_expiry(): void
    {
        [$administrator, $sessionId, $session] = $this->makePlatformSession(now()->subMinute(), now()->subSecond());

        $response = $this->actingAs($administrator, 'platform')
            ->withSession(['platform_mfa_verified_at' => now()->timestamp])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->get(route('platform.dashboard'));

        $response->assertRedirect(route('platform.login'));
        $this->assertNotNull($session->fresh()->revoked_at);
        $this->assertGuest('platform');
    }

    /** @return array{PlatformUser, string, PlatformSession} */
    private function makePlatformSession(Carbon $lastActivity, Carbon $expiresAt): array
    {
        $this->seed(PlatformRoleSeeder::class);
        $administrator = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $administrator->roles()->attach($role->id);
        $sessionId = app('session.store')->getId();
        $session = PlatformSession::query()->create([
            'platform_user_id' => $administrator->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now()->subHour(),
            'last_activity_at' => $lastActivity,
            'expires_at' => $expiresAt,
        ]);

        return [$administrator, $sessionId, $session];
    }
}
