<?php

namespace Tests\Feature;

use App\Models\PlatformSecurityEvent;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformNewLoginAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_displays_an_encrypted_totp_secret_and_rejects_an_invalid_code(): void
    {
        $user = PlatformUser::factory()->create();
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'Test-password-123!',
        ]);
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId());

        $response = $this->get(route('platform.mfa.setup'));
        $response->assertOk()->assertSee('Manual setup key')->assertSee('TOTP enrollment QR code');
        $rawSecret = DB::table('platform_users')->where('id', $user->id)->value('totp_secret');
        $this->assertNotSame($user->fresh()->totp_secret, $rawSecret);

        $this->post(route('platform.mfa.setup.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->mfa_confirmed_at);
    }

    public function test_totp_challenge_completes_login_and_creates_a_tracked_session(): void
    {
        $this->travelTo('1970-01-01 00:00:59');
        $user = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [],
        ]);
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'Test-password-123!',
        ])->assertRedirect(route('platform.mfa.challenge'));
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId());
        $preMfaSessionId = app('session.store')->getId();

        $this->post(route('platform.mfa.verify'), ['code' => '287082'])->assertRedirect(route('platform.dashboard'));

        $this->assertDatabaseCount('platform_sessions', 1);
        $session = PlatformSession::query()->firstOrFail();
        $this->assertNotSame(hash('sha256', $preMfaSessionId), $session->session_hash);
        $this->assertFalse((bool) session('platform_mfa_pending'));
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.mfa.challenge_succeeded']);
    }

    public function test_recovery_code_is_consumed_and_requires_new_totp_enrollment(): void
    {
        $user = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [Hash::make('ABCD-EFGH')],
        ]);
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'Test-password-123!',
        ]);
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId());

        $this->post(route('platform.mfa.verify'), ['code' => 'ABCD-EFGH'])->assertRedirect();

        $this->assertNull($user->fresh()->mfa_confirmed_at);
        $this->assertNull($user->fresh()->totp_secret);
        $this->assertNull($user->fresh()->recovery_code_hashes);
        $this->assertDatabaseHas('platform_security_events', ['event_type' => 'platform.mfa.recovery_used']);
    }

    public function test_mfa_challenges_are_rate_limited_per_platform_user(): void
    {
        $user = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [],
        ]);
        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'Test-password-123!',
        ])->assertRedirect(route('platform.mfa.challenge'));
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId());

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('platform.mfa.verify'), ['code' => '000000'])->assertRedirect();
        }

        $this->post(route('platform.mfa.verify'), ['code' => '000000'])->assertTooManyRequests();
        $this->assertSame(6, PlatformSecurityEvent::query()->where('event_type', 'platform.mfa.challenge_failed')->count());
    }

    public function test_a_successful_login_from_a_different_client_records_and_sends_a_security_alert(): void
    {
        $this->travelTo('1970-01-01 00:00:59');
        $user = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [],
        ]);
        PlatformSession::query()->create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', 'recent-prior-session'),
            'session_identifier' => 'recent-prior-session',
            'ip_address' => '192.0.2.15',
            'user_agent' => 'Earlier Browser',
            'logged_in_at' => now()->subMinutes(5),
            'last_activity_at' => now()->subMinute(),
            'expires_at' => now()->addHours(8),
        ]);
        Notification::fake();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.8', 'HTTP_USER_AGENT' => 'New Browser'])
            ->post(route('platform.login.store'), [
                'email' => $user->email,
                'password' => 'Test-password-123!',
            ])->assertRedirect(route('platform.mfa.challenge'));
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId());

        $this->post(route('platform.mfa.verify'), ['code' => '287082'])->assertRedirect(route('platform.dashboard'));

        $this->assertDatabaseHas('platform_security_events', [
            'platform_user_id' => $user->id,
            'event_type' => 'platform.login.unfamiliar_client',
            'ip_address' => '198.51.100.8',
        ]);
        Notification::assertSentTo($user, PlatformNewLoginAlert::class, function (PlatformNewLoginAlert $notification): bool {
            return $notification->ipAddress === '198.51.100.8'
                && $notification->userAgent === 'New Browser';
        });
    }
}
