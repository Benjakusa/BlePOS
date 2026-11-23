<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformSessionController extends Controller
{
    public function index(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.sessions.revoke');

        return view('platform.security.sessions', [
            'sessions' => PlatformSession::query()->with('user')->whereNull('revoked_at')->where('expires_at', '>', now())->orderByDesc('last_activity_at')->paginate(50),
        ]);
    }

    public function destroy(Request $request, PlatformSession $platformSession, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.sessions.revoke');

        abort_if($platformSession->revoked_at, 404);
        $platformSession->update(['revoked_at' => now()]);
        DB::table('sessions')->where('id', $platformSession->session_identifier)->delete();
        $audit->record($request->user('platform')->id, 'platform.session.revoked', PlatformUser::class, (string) $platformSession->platform_user_id, ['session_id' => $platformSession->id]);

        return back()->with('status', 'Platform session revoked.');
    }

    public function revokeAll(Request $request, PlatformUser $platformUser, PlatformAuditRecorder $audit, PlatformPermissionAuthorizer $authorizer): RedirectResponse
    {
        $authorizer->ensure($request->user('platform'), 'platform.security.sessions.revoke');

        $sessions = $platformUser->sessions()->whereNull('revoked_at')->get();
        foreach ($sessions as $session) {
            DB::table('sessions')->where('id', $session->session_identifier)->delete();
        }
        $platformUser->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $audit->record($request->user('platform')->id, 'platform.sessions.revoked_all', PlatformUser::class, (string) $platformUser->id, ['count' => $sessions->count()]);

        return back()->with('status', 'All platform sessions for that user were revoked.');
    }
}
