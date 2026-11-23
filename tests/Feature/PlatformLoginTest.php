<?php

namespace Tests\Feature;

use App\Models\PlatformUser;
use App\Models\User;
use App\Notifications\PlatformAccountLocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_dashboard_redirects_an_unauthenticated_request_to_platform_login(): void
    {
        $this->get(route('platform.dashboard'))->assertRedirect(route('platform.login'));
    }

    public function test_tenant_user_credentials_do_not_authenticate_to_the_platform_console(): void
    {
        $tenantUser = User::factory()->create(['email' => 'tenant@example.test', 'password' => 'Tenant-password-123!']);

        $response = $this->post(route('platform.login.store'), [
            'email' => $tenantUser->email,
            'password' => 'Tenant-password-123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('platform');
        $this->assertDatabaseHas('platform_security_events', ['event_type' => 'platform.login.failed']);
    }

    public function test_platform_password_login_requires_mfa_before_the_console(): void
    {
        $user = PlatformUser::factory()->create();

        $response = $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'Test-password-123!',
        ]);

        $response->assertRedirect(route('platform.mfa.setup'));
        $this->assertAuthenticatedAs($user, 'platform');
        $this->assertTrue(session('platform_mfa_pending'));
        $this->withCookie(config('session.platform.cookie'), app('session.store')->getId())
            ->get(route('platform.dashboard'))->assertRedirect(route('platform.mfa.setup'));
    }

    public function test_failed_password_attempts_are_recorded_and_account_locks_after_five(): void
    {
        $user = PlatformUser::factory()->create();
        Notification::fake();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('platform.login'))->post(route('platform.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirect(route('platform.login'));
        }

        $this->assertDatabaseHas('platform_users', ['id' => $user->id, 'failed_login_attempts' => 5]);
        $this->assertNotNull($user->fresh()->locked_until);
        Notification::assertSentTo($user, PlatformAccountLocked::class);
    }

    public function test_repeated_login_lockouts_increase_from_fifteen_to_thirty_minutes(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $user = PlatformUser::factory()->create();
        Notification::fake();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('platform.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $this->assertSame('2026-01-01 00:15:00', $user->fresh()->locked_until->toDateTimeString());
        $this->travelTo('2026-01-01 00:15:01');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('platform.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $this->assertSame('2026-01-01 00:45:01', $user->fresh()->locked_until->toDateTimeString());
        Notification::assertSentTo($user, PlatformAccountLocked::class, function (PlatformAccountLocked $notification): bool {
            return $notification->lockedUntil->toDateTimeString() === '2026-01-01 00:45:01';
        });
    }

    public function test_platform_login_rate_limit_applies_per_account(): void
    {
        $user = PlatformUser::factory()->create();

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->post(route('platform.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $this->post(route('platform.login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }

    public function test_platform_login_rate_limit_applies_per_ip_across_accounts(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.14']);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('platform.login.store'), [
                'email' => 'unknown-'.$attempt.'@example.test',
                'password' => 'incorrect-password',
            ])->assertRedirect();
        }

        $this->post(route('platform.login.store'), [
            'email' => 'another@example.test',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }
}
