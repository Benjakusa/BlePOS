<?php

namespace Tests\Feature;

use App\Mail\PlatformInvitationMail;
use App\Models\PlatformInvitation;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PlatformInvitationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_invitation_creates_a_platform_user_with_mfa_required(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $inviter = PlatformUser::factory()->create();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $token = str_repeat('a', 64);
        $invitation = PlatformInvitation::query()->create([
            'email' => 'new-staff@example.test',
            'platform_role_id' => $role->id,
            'invited_by' => $inviter->id,
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'expires_at' => now()->addDays(2),
        ]);
        $signedUrl = URL::temporarySignedRoute('platform.invitation.accept', now()->addHour(), ['token' => $token]);

        $response = $this->post($signedUrl, [
            'name' => 'New Platform Staff',
            'password' => 'New-platform-User-123!',
            'password_confirmation' => 'New-platform-User-123!',
        ]);

        $response->assertRedirect(route('platform.login'));
        $user = PlatformUser::query()->where('email', 'new-staff@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('New-platform-User-123!', $user->password));
        $this->assertNull($user->mfa_confirmed_at);
        $this->assertTrue($user->roles()->whereKey($role->id)->exists());
        $this->assertDatabaseHas('platform_invitations', ['id' => $invitation->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.invitation.accepted']);
    }

    public function test_an_expired_invitation_is_rejected_even_with_a_still_valid_signature(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $inviter = PlatformUser::factory()->create();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $token = str_repeat('b', 64);
        PlatformInvitation::query()->create([
            'email' => 'expired-staff@example.test',
            'platform_role_id' => $role->id,
            'invited_by' => $inviter->id,
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
        $signedUrl = URL::temporarySignedRoute('platform.invitation.accept', now()->addDays(3), ['token' => $token]);
        $this->travel(2)->days();

        $response = $this->post($signedUrl, [
            'name' => 'Expired Staff',
            'password' => 'New-platform-User-123!',
            'password_confirmation' => 'New-platform-User-123!',
        ]);

        $response->assertGone();
        $this->assertDatabaseMissing('platform_users', ['email' => 'expired-staff@example.test']);
    }

    public function test_inviting_platform_staff_sends_the_link_and_records_the_invitation(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        Mail::fake();

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.users.invite'), [
                'email' => 'invited-staff@example.test',
                'platform_role_id' => $role->id,
                'reason' => 'Add staff member for customer support.',
            ]);

        $response->assertRedirect();
        $invitation = PlatformInvitation::query()->where('email', 'invited-staff@example.test')->firstOrFail();
        $this->assertSame('pending', $invitation->status);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $invitation->token_hash);
        Mail::assertSent(PlatformInvitationMail::class, function (PlatformInvitationMail $mail): bool {
            return $mail->hasTo('invited-staff@example.test')
                && str_contains($mail->inviteUrl, '/platform-console/invitations/');
        });
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.invitation.created']);
    }

    public function test_failed_invitation_delivery_cancels_the_link_and_records_security_and_audit_events(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        Mail::shouldReceive('to')
            ->once()
            ->with('delivery-failure@example.test')
            ->andThrow(new \RuntimeException('Mail transport credentials must not be logged.'));

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.users.invite'), [
                'email' => 'delivery-failure@example.test',
                'platform_role_id' => $role->id,
                'reason' => 'Add staff member to cover support duties.',
            ]);

        $response->assertSessionHasErrors('email');
        $invitation = PlatformInvitation::query()->where('email', 'delivery-failure@example.test')->firstOrFail();
        $this->assertSame('cancelled', $invitation->status);
        $this->assertDatabaseHas('platform_security_events', [
            'event_type' => 'platform.invitation.delivery_failed',
            'platform_user_id' => $administrator->id,
        ]);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.invitation.delivery_failed',
            'actor_id' => $administrator->id,
            'target_id' => (string) $invitation->id,
        ]);
        $this->assertDatabaseMissing('platform_audit_log', ['details->reason' => 'Mail transport credentials must not be logged.']);
    }

    public function test_resending_an_expired_invitation_sends_a_replacement_and_cancels_old_link(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $oldInvitation = PlatformInvitation::query()->create([
            'email' => 'resend-staff@example.test',
            'platform_role_id' => $role->id,
            'invited_by' => $administrator->id,
            'token_hash' => hash('sha256', 'old-invitation-token'),
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);
        Mail::fake();

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.users.invitations.resend', $oldInvitation), [
                'reason' => 'The prior invite link expired before use.',
            ]);

        $response->assertRedirect();
        $this->assertSame('cancelled', $oldInvitation->fresh()->status);
        $replacement = PlatformInvitation::query()->where('email', 'resend-staff@example.test')->where('status', 'pending')->firstOrFail();
        $this->assertNotSame($oldInvitation->token_hash, $replacement->token_hash);
        Mail::assertSent(PlatformInvitationMail::class, fn (PlatformInvitationMail $mail): bool => $mail->hasTo('resend-staff@example.test'));
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.invitation.resent']);
    }

    public function test_an_administrator_can_cancel_a_pending_invitation_with_a_reason(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        [$administrator, $sessionId] = $this->makeAuthenticatedAdministrator();
        $role = PlatformRole::query()->where('slug', 'support-agent')->firstOrFail();
        $invitation = PlatformInvitation::query()->create([
            'email' => 'cancel-staff@example.test',
            'platform_role_id' => $role->id,
            'invited_by' => $administrator->id,
            'token_hash' => hash('sha256', 'cancel-invitation-token'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($administrator, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->delete(route('platform.users.invitations.cancel', $invitation), [
                'reason' => 'The requested access is no longer needed.',
            ]);

        $response->assertRedirect();
        $this->assertSame('cancelled', $invitation->fresh()->status);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.invitation.cancelled']);
    }

    /** @return array{PlatformUser, string} */
    private function makeAuthenticatedAdministrator(): array
    {
        $administrator = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
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
