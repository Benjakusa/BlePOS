<?php

namespace Tests\Feature;

use App\Models\PlatformUser;
use App\Notifications\PlatformPasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PlatformPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_requests_are_generic_and_send_the_platform_specific_notification(): void
    {
        Notification::fake();
        $user = PlatformUser::factory()->create();

        $this->post(route('platform.password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that platform staff account exists, password reset instructions have been sent.');

        Notification::assertSentTo($user, PlatformPasswordResetNotification::class);
        $this->assertDatabaseHas('platform_password_reset_tokens', ['email' => $user->email]);

        $this->post(route('platform.password.email'), ['email' => 'missing@example.test'])
            ->assertSessionHas('status', 'If that platform staff account exists, password reset instructions have been sent.');
        Notification::assertCount(1);
    }

    public function test_valid_platform_reset_token_changes_the_password_and_cannot_be_reused(): void
    {
        $user = PlatformUser::factory()->create();
        $token = Password::broker('platform')->createToken($user);

        $credentials = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-platform-password-123!',
            'password_confirmation' => 'New-platform-password-123!',
        ];
        $this->post(route('platform.password.update'), $credentials)->assertRedirect(route('platform.login'));

        $this->assertTrue(Hash::check('New-platform-password-123!', $user->fresh()->password));
        $this->assertDatabaseMissing('platform_password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.password.reset']);
        $this->post(route('platform.password.update'), $credentials)->assertSessionHasErrors('email');
    }
}
