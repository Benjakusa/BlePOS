<?php

namespace Tests\Feature;

use App\Models\PlatformApproval;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformRoleChanged;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlatformApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_different_platform_administrator_can_approve_a_super_administrator_grant(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $requester = PlatformUser::factory()->create();
        $approver = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $target = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $approver->roles()->attach($superAdminRole->id);
        $approval = $this->makeApproval($requester, $target, $superAdminRole);
        $sessionId = $this->makeTrackedSession($approver);
        Notification::fake();

        $response = $this->actingAs($approver, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.approvals.approve', $approval));

        $response->assertRedirect();
        $this->assertDatabaseHas('platform_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
            'requested_by' => $requester->id,
            'approved_by' => $approver->id,
        ]);
        $this->assertDatabaseHas('platform_role_user', [
            'platform_user_id' => $target->id,
            'platform_role_id' => $superAdminRole->id,
        ]);
        Notification::assertSentTo($target, PlatformRoleChanged::class, function (PlatformRoleChanged $notification): bool {
            return $notification->change === 'assigned to your account'
                && $notification->reason === 'Grant platform ownership to the on-call administrator.';
        });
    }

    public function test_an_approval_requester_cannot_approve_their_own_super_administrator_grant(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $requester = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $target = PlatformUser::factory()->create();
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $requester->roles()->attach($superAdminRole->id);
        $approval = $this->makeApproval($requester, $target, $superAdminRole);
        $sessionId = $this->makeTrackedSession($requester);

        $response = $this->actingAs($requester, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.approvals.approve', $approval));

        $response->assertForbidden();
        $this->assertDatabaseHas('platform_approvals', ['id' => $approval->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('platform_role_user', [
            'platform_user_id' => $target->id,
            'platform_role_id' => $superAdminRole->id,
        ]);
    }

    public function test_a_requester_can_cancel_a_pending_approval_with_an_audited_reason(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $requester = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $target = PlatformUser::factory()->create();
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $requester->roles()->attach($superAdminRole->id);
        $approval = $this->makeApproval($requester, $target, $superAdminRole);
        $sessionId = $this->makeTrackedSession($requester);

        $response = $this->actingAs($requester, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId)
            ->post(route('platform.approvals.cancel', $approval), [
                'reason' => 'The administrator role is no longer required.',
            ]);

        $response->assertRedirect();
        $approval->refresh();
        $this->assertSame('cancelled', $approval->status);
        $this->assertSame($requester->id, $approval->cancelled_by);
        $this->assertSame('The administrator role is no longer required.', $approval->cancellation_reason);
        $this->assertDatabaseHas('platform_audit_log', ['action' => 'platform.approval.cancelled']);
    }

    public function test_expired_pending_approvals_are_marked_and_audited_by_the_scheduled_command(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $requester = PlatformUser::factory()->create();
        $target = PlatformUser::factory()->create();
        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $approval = $this->makeApproval($requester, $target, $superAdminRole);
        $approval->update(['expires_at' => now()->subMinute()]);

        $this->artisan('platform:expire-approvals')
            ->expectsOutput('Expired 1 platform approval(s).')
            ->assertSuccessful();

        $this->assertSame('expired', $approval->fresh()->status);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.approval.expired',
            'target_id' => (string) $approval->id,
        ]);
    }

    private function makeApproval(PlatformUser $requester, PlatformUser $target, PlatformRole $role): PlatformApproval
    {
        return PlatformApproval::query()->create([
            'action_type' => 'platform.superadmin.grant',
            'target_type' => PlatformUser::class,
            'target_id' => (string) $target->id,
            'requested_by' => $requester->id,
            'status' => 'pending',
            'reason' => 'Grant platform ownership to the on-call administrator.',
            'payload' => ['platform_role_id' => $role->id],
            'expires_at' => now()->addDays(1),
        ]);
    }

    private function makeTrackedSession(PlatformUser $user): string
    {
        $sessionId = app('session.store')->getId();
        PlatformSession::query()->create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return $sessionId;
    }
}
