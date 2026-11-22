<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\PlatformInvitationMail;
use App\Models\PlatformApproval;
use App\Models\PlatformInvitation;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PlatformUserController extends Controller
{
    public function index(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.view');

        return view('platform.security.users', [
            'users' => PlatformUser::query()->with('roles')->orderBy('name')->paginate(25),
            'roles' => PlatformRole::query()->orderBy('name')->get(),
            'invitations' => PlatformInvitation::query()->with('role')->where('status', 'pending')->orderByDesc('created_at')->get(),
        ]);
    }

    public function invite(Request $request, PlatformAuditRecorder $audit, PlatformSecurityEvents $events, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.invite');

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', 'unique:platform_users,email'],
            'platform_role_id' => ['required', 'integer', 'exists:platform_roles,id'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $role = PlatformRole::query()->findOrFail($validated['platform_role_id']);
        abort_if($role->slug === 'super-administrator', 422, 'Super Administrator grants require two-person approval.');

        $now = now();
        $token = Str::random(64);
        $invitation = PlatformInvitation::query()->create([
            'email' => mb_strtolower($validated['email']),
            'platform_role_id' => $role->id,
            'invited_by' => $request->user('platform')->id,
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'expires_at' => $now->copy()->addDays(3),
        ]);
        $inviteUrl = URL::temporarySignedRoute('platform.invitation.accept', $invitation->expires_at, ['token' => $token]);

        try {
            Mail::to($invitation->email)->send(new PlatformInvitationMail($inviteUrl, $invitation->expires_at));
        } catch (Throwable) {
            DB::transaction(function () use ($request, $invitation, $validated, $events, $audit): void {
                $invitation->update(['status' => 'cancelled']);
                $metadata = [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                    'reason' => $validated['reason'],
                ];
                $events->record($request, 'platform.invitation.delivery_failed', 'warning', $request->user('platform')->id, $metadata);
                $audit->record($request->user('platform')->id, 'platform.invitation.delivery_failed', PlatformInvitation::class, (string) $invitation->id, $metadata);
            });

            return back()->withErrors(['email' => 'The invitation could not be delivered. Check platform email configuration and try again.']);
        }

        PlatformInvitation::query()->where('email', $invitation->email)
            ->where('status', 'pending')->where('id', '!=', $invitation->id)->update(['status' => 'cancelled']);
        $audit->record($request->user('platform')->id, 'platform.invitation.created', PlatformInvitation::class, (string) $invitation->id, [
            'email' => $invitation->email,
            'role' => $role->slug,
            'reason' => $validated['reason'],
            'expires_at' => $invitation->expires_at->toISOString(),
        ]);

        return back()->with('status', 'Invitation sent. The link expires in three days.');
    }

    public function resendInvitation(Request $request, PlatformInvitation $platformInvitation, PlatformAuditRecorder $audit, PlatformSecurityEvents $events, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.invite');

        abort_unless($platformInvitation->status === 'pending', 409, 'Only a pending invitation can be resent.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        $token = Str::random(64);
        $replacement = PlatformInvitation::query()->create([
            'email' => $platformInvitation->email,
            'platform_role_id' => $platformInvitation->platform_role_id,
            'invited_by' => $request->user('platform')->id,
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);
        $inviteUrl = URL::temporarySignedRoute('platform.invitation.accept', $replacement->expires_at, ['token' => $token]);

        try {
            Mail::to($replacement->email)->send(new PlatformInvitationMail($inviteUrl, $replacement->expires_at));
        } catch (Throwable) {
            DB::transaction(function () use ($request, $platformInvitation, $replacement, $validated, $events, $audit): void {
                $replacement->update(['status' => 'cancelled']);
                $metadata = [
                    'invitation_id' => $replacement->id,
                    'replaced_invitation_id' => $platformInvitation->id,
                    'email' => $replacement->email,
                    'reason' => $validated['reason'],
                ];
                $events->record($request, 'platform.invitation.delivery_failed', 'warning', $request->user('platform')->id, $metadata);
                $audit->record($request->user('platform')->id, 'platform.invitation.delivery_failed', PlatformInvitation::class, (string) $replacement->id, $metadata);
            });

            return back()->withErrors(['email' => 'The invitation could not be delivered. The existing invitation remains available.']);
        }

        PlatformInvitation::query()->where('email', $replacement->email)
            ->where('status', 'pending')->where('id', '!=', $replacement->id)->update(['status' => 'cancelled']);
        $audit->record($request->user('platform')->id, 'platform.invitation.resent', PlatformInvitation::class, (string) $replacement->id, [
            'replaced_invitation_id' => $platformInvitation->id,
            'reason' => $validated['reason'],
            'expires_at' => $replacement->expires_at->toISOString(),
        ]);

        return back()->with('status', 'A new invitation link was sent. The previous link is now cancelled.');
    }

    public function cancelInvitation(Request $request, PlatformInvitation $platformInvitation, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.invite');

        abort_unless($platformInvitation->status === 'pending', 409, 'Only a pending invitation can be cancelled.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        $platformInvitation->update(['status' => 'cancelled']);
        $audit->record($request->user('platform')->id, 'platform.invitation.cancelled', PlatformInvitation::class, (string) $platformInvitation->id, [
            'email' => $platformInvitation->email,
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', 'Invitation cancelled.');
    }

    public function showInvitation(string $token): View
    {
        $invitation = $this->pendingInvitation($token);

        return view('platform.auth.accept-invitation', ['invitation' => $invitation, 'token' => $token]);
    }

    public function acceptInvitation(Request $request, string $token, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'string', PasswordRule::min(14)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $user = DB::transaction(function () use ($token, $validated): PlatformUser {
            $invitation = PlatformInvitation::query()->where('token_hash', hash('sha256', $token))
                ->where('status', 'pending')->lockForUpdate()->firstOrFail();
            abort_if($invitation->expires_at->isPast(), 410, 'This invitation has expired.');
            abort_if(PlatformUser::query()->where('email', $invitation->email)->exists(), 409, 'An account already exists for this email.');

            $user = PlatformUser::query()->create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
                'status' => 'active',
            ]);
            $user->roles()->attach($invitation->platform_role_id, ['assigned_by' => $invitation->invited_by]);
            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);

            return $user;
        });

        $audit->record($user->id, 'platform.invitation.accepted', PlatformUser::class, (string) $user->id, ['email' => $user->email]);

        return redirect()->route('platform.login')->with('status', 'Your platform account is ready. Sign in to enroll multi-factor authentication.');
    }

    public function updateStatus(Request $request, PlatformUser $platformUser, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.status');

        $validated = $request->validate([
            'status' => ['required', 'in:active,locked,disabled'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $platformUser, $validated, $audit): void {
            $target = PlatformUser::query()->whereKey($platformUser->id)->lockForUpdate()->firstOrFail();
            abort_if($request->user('platform')->is($target), 422, 'You cannot disable or lock your own account.');
            $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->lockForUpdate()->first();
            if ($validated['status'] !== 'active' && $superAdminRole && $target->roles()->whereKey($superAdminRole->id)->exists()) {
                $activeSuperAdmins = $superAdminRole->users()->where('platform_users.status', 'active')->count();
                abort_if($activeSuperAdmins <= 1, 409, 'The final active Super Administrator cannot be disabled or locked.');
            }

            $before = $target->status;
            $statusChanges = ['status' => $validated['status']];
            if ($validated['status'] === 'active') {
                $statusChanges['locked_until'] = null;
                $statusChanges['failed_login_attempts'] = 0;
            }
            $target->forceFill($statusChanges)->save();
            if ($validated['status'] !== 'active') {
                $this->revokeSessions($target);
            }
            $audit->record($request->user('platform')->id, 'platform.user.status_changed', PlatformUser::class, (string) $target->id, [
                'before' => $before,
                'after' => $validated['status'],
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('status', 'Platform user status updated.');
    }

    public function requestMfaReset(Request $request, PlatformUser $platformUser, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.mfa-reset.request');

        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        abort_if($request->user('platform')->is($platformUser), 422, 'Use recovery codes to reset your own MFA.');

        $approval = PlatformApproval::query()->create([
            'action_type' => 'platform.mfa.reset',
            'target_type' => PlatformUser::class,
            'target_id' => (string) $platformUser->id,
            'requested_by' => $request->user('platform')->id,
            'status' => 'pending',
            'reason' => $validated['reason'],
            'payload' => [],
            'expires_at' => now()->addDays(7),
        ]);
        $audit->record($request->user('platform')->id, 'platform.mfa.reset_requested', PlatformUser::class, (string) $platformUser->id, ['approval_id' => $approval->id, 'reason' => $validated['reason']]);

        return back()->with('status', 'MFA reset request submitted for a second platform approver.');
    }

    public function requirePasswordReset(Request $request, PlatformUser $platformUser, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.users.password-reset');

        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        abort_if($request->user('platform')->is($platformUser), 422, 'Use the password reset flow to update your own password.');
        $status = Password::broker('platform')->sendResetLink(['email' => $platformUser->email]);
        abort_unless($status === Password::RESET_LINK_SENT, 503, 'The platform password reset message could not be sent.');
        $platformUser->forceFill(['must_reset_password' => true])->save();
        $this->revokeSessions($platformUser);
        $audit->record($request->user('platform')->id, 'platform.user.password_reset_required', PlatformUser::class, (string) $platformUser->id, ['reason' => $validated['reason']]);

        return back()->with('status', 'Password reset instructions sent. The user must set a new password before using the console.');
    }

    private function pendingInvitation(string $token): PlatformInvitation
    {
        $invitation = PlatformInvitation::query()->with('role')
            ->where('token_hash', hash('sha256', $token))
            ->where('status', 'pending')->firstOrFail();
        abort_if($invitation->expires_at->isPast(), 410, 'This invitation has expired.');

        return $invitation;
    }

    private function revokeSessions(PlatformUser $user): void
    {
        $sessions = $user->sessions()->whereNull('revoked_at')->get();
        foreach ($sessions as $session) {
            DB::table('sessions')->where('id', $session->session_identifier)->delete();
        }
        $user->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
