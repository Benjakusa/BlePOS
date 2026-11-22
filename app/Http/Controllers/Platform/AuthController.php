<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformAccountLocked;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformSecurityEvents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('platform.auth.login');
    }

    public function store(Request $request, PlatformSecurityEvents $events, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $emailAddress = mb_strtolower($validated['email']);
        $user = PlatformUser::query()->where('email', $emailAddress)->first();
        $now = now();

        if ($user && $user->locked_until?->isFuture()) {
            $events->record($request, 'platform.login.blocked_locked', 'warning', $user->id);

            return back()->withErrors(['email' => 'These credentials are invalid or the account is unavailable.'])->onlyInput('email');
        }

        if (! $user || $user->status !== 'active' || ! Hash::check($validated['password'], $user->password)) {
            if ($user) {
                $attempts = min(65535, $user->failed_login_attempts + 1);
                $lockTier = min(2, intdiv(max(0, $attempts - 5), 5));
                $shouldLock = $attempts >= 5 && ($attempts - 5) % 5 === 0;
                $lockedUntil = $shouldLock ? $now->copy()->addMinutes(15 * (2 ** $lockTier)) : null;
                $user->forceFill([
                    'failed_login_attempts' => $attempts,
                    'locked_until' => $lockedUntil,
                ])->save();
                if ($lockedUntil) {
                    $user->notify(new PlatformAccountLocked($lockedUntil));
                }
            }

            $events->record($request, 'platform.login.failed', 'warning', $user?->id);

            return back()->withErrors(['email' => 'These credentials are invalid or the account is unavailable.'])->onlyInput('email');
        }

        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        Auth::guard('platform')->login($user, false);
        $request->session()->regenerate();
        $events->record($request, 'platform.login.password_accepted', 'info', $user->id);
        $audit->record($user->id, 'platform.login.password_accepted', PlatformUser::class, (string) $user->id);
        if ($user->must_reset_password) {
            $request->session()->put('platform_password_reset_pending', true);

            return redirect()->route('platform.password.change');
        }
        $request->session()->put('platform_mfa_pending', true);

        return redirect()->route($user->mfa_confirmed_at ? 'platform.mfa.challenge' : 'platform.mfa.setup');
    }

    public function destroy(Request $request, PlatformAuditRecorder $audit): RedirectResponse
    {
        $user = $request->user('platform');
        if ($user) {
            $audit->record($user->id, 'platform.logout', PlatformUser::class, (string) $user->id);
            PlatformSession::query()
                ->where('session_hash', hash('sha256', $request->session()->getId()))
                ->where('platform_user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
