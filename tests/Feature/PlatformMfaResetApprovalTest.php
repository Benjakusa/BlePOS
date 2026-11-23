<?php

namespace Tests\Feature;

use App\Models\PlatformApproval;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformMfaReset;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformMfaResetApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_mfa_reset_clears_credentials_revokes_sessions_and_notifies_the_user(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $requester = PlatformUser::factory()->create();
        $approver = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $target = PlatformUser::factory()->create([
            'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => [Hash::make('ABCD-EFGH')],
        ]);
        $securityRole = PlatformRole::query()->where('slug', 'security-officer')->firstOrFail();
        $approver->roles()->attach($securityRole->id);
        $approval = PlatformApproval::query()->create([
            'action_type' => 'platform.mfa.reset',
            'target_type' => PlatformUser::class,
            'target_id' => (string) $target->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
            'reason' => 'The administrator lost access to their authenticator device.',
            'payload' => [],
            'expires_at' => now()->addDay(),
        ]);
        $targetSession = PlatformSession::query()->create([
            'platform_user_id' => $target->id,
            'session_hash' => hash('sha256', 'target-active-session'),
            'session_identifier' => 'target-active-session',
            'logged_in_at' => now()->subHour(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(7),
        ]);
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $approver->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);
        Notification::fake();

        $response = $this->actingAs($approver, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.approvals.approve', $approval));

        $response->assertRedirect();
        $this->assertNull($target->fresh()->totp_secret);
        $this->assertNull($target->fresh()->mfa_confirmed_at);
        $this->assertNull($target->fresh()->recovery_code_hashes);
        $this->assertNotNull($targetSession->fresh()->revoked_at);
        $this->assertSame('approved', $approval->fresh()->status);
        Notification::assertSentTo($target, PlatformMfaReset::class, function (PlatformMfaReset $notification): bool {
            return $notification->changedBy !== ''
                && $notification->reason === 'The administrator lost access to their authenticator device.';
        });
    }
}
