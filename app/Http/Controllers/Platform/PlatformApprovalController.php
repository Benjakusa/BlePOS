<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformApproval;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Notifications\PlatformMfaReset;
use App\Notifications\PlatformRoleChanged;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformApprovalController extends Controller
{
    public function index(): View
    {
        return view('platform.security.approvals', [
            'approvals' => PlatformApproval::query()->with('requester')->where('status', 'pending')->orderBy('created_at')->get(),
        ]);
    }

    public function approve(Request $request, PlatformApproval $platformApproval, PlatformAuditRecorder $audit): RedirectResponse
    {
        abort_unless($platformApproval->status === 'pending', 409);
        abort_if($platformApproval->requested_by === $request->user('platform')->id, 403, 'The requester cannot approve their own action.');
        abort_if($platformApproval->expires_at?->isPast(), 410, 'This approval request has expired.');

        DB::transaction(function () use ($request, $platformApproval, $audit): void {
            $approval = PlatformApproval::query()->whereKey($platformApproval->id)->lockForUpdate()->firstOrFail();
            abort_unless($approval->status === 'pending', 409);
            abort_if($approval->requested_by === $request->user('platform')->id, 403);

            if ($approval->action_type === 'platform.superadmin.grant') {
                $target = PlatformUser::query()->findOrFail((int) $approval->target_id);
                abort_unless($target->status === 'active', 409, 'The target platform user is not active.');
                $role = PlatformRole::query()->where('slug', 'super-administrator')->findOrFail($approval->payload['platform_role_id']);
                $target->roles()->syncWithoutDetaching([$role->id => ['assigned_by' => $request->user('platform')->id]]);
                $this->revokeSessions($target);
                $audit->record($request->user('platform')->id, 'platform.superadmin.grant_approved', PlatformUser::class, (string) $target->id, ['request_id' => $approval->id]);
                $target->notify(new PlatformRoleChanged(
                    $role->name,
                    'assigned to your account',
                    $approval->reason,
                    $request->user('platform')->name,
                ));
            } elseif ($approval->action_type === 'platform.mfa.reset') {
                $target = PlatformUser::query()->findOrFail((int) $approval->target_id);
                $target->forceFill(['totp_secret' => null, 'mfa_confirmed_at' => null, 'recovery_code_hashes' => null])->save();
                $this->revokeSessions($target);
                $audit->record($request->user('platform')->id, 'platform.mfa.reset_approved', PlatformUser::class, (string) $target->id, ['request_id' => $approval->id]);
                $target->notify(new PlatformMfaReset($request->user('platform')->name, $approval->reason));
            } else {
                abort(422, 'Unsupported approval action.');
            }

            $approval->update(['status' => 'approved', 'approved_by' => $request->user('platform')->id, 'approved_at' => now()]);
        });

        return back()->with('status', 'Approval completed. The requester and target are recorded in the audit log.');
    }

    public function cancel(Request $request, PlatformApproval $platformApproval, PlatformAuditRecorder $audit): RedirectResponse
    {
        abort_unless($platformApproval->requested_by === $request->user('platform')->id, 403, 'Only the requester can cancel this approval.');
        abort_unless($platformApproval->status === 'pending', 409, 'Only pending approval requests can be cancelled.');
        abort_if($platformApproval->expires_at?->isPast(), 410, 'This approval request has expired.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);

        DB::transaction(function () use ($request, $platformApproval, $validated, $audit): void {
            $approval = PlatformApproval::query()->whereKey($platformApproval->id)->lockForUpdate()->firstOrFail();
            abort_unless($approval->requested_by === $request->user('platform')->id, 403);
            abort_unless($approval->status === 'pending', 409);
            abort_if($approval->expires_at?->isPast(), 410);
            $approval->update([
                'status' => 'cancelled',
                'cancelled_by' => $request->user('platform')->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ]);
            $audit->record($request->user('platform')->id, 'platform.approval.cancelled', PlatformApproval::class, (string) $approval->id, [
                'action_type' => $approval->action_type,
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('status', 'Approval request cancelled.');
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
