<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\PlatformAuditRecorder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PlatformPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('platform.auth.password-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        Password::broker('platform')->sendResetLink(['email' => mb_strtolower($validated['email'])]);

        return back()->with('status', 'If that platform staff account exists, password reset instructions have been sent.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('platform.auth.password-reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', 'string', PasswordRule::min(14)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $status = Password::broker('platform')->reset($validated, function (PlatformUser $user, string $password) use ($audit): void {
            $user->forceFill([
                'password' => $password,
                'must_reset_password' => false,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();
            $this->revokeSessions($user);
            event(new PasswordReset($user));
            $audit->record($user->id, 'platform.password.reset', PlatformUser::class, (string) $user->id);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'The reset link is invalid, expired, or already used.'])->withInput($request->only('email'));
        }

        return redirect()->route('platform.login')->with('status', 'Password changed. Sign in and complete multi-factor authentication.');
    }

    public function forceChangeForm(Request $request): View
    {
        abort_unless($request->session()->get('platform_password_reset_pending') === true, 403);

        return view('platform.auth.password-change-required');
    }

    public function forceChange(Request $request, PlatformAuditRecorder $audit): RedirectResponse
    {
        abort_unless($request->session()->get('platform_password_reset_pending') === true, 403);
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'string', PasswordRule::min(14)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $user = $request->user('platform');
        if (! $user || ! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is invalid.']);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_reset_password' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();
        $this->revokeSessions($user);
        $request->session()->forget('platform_password_reset_pending');
        $request->session()->put('platform_mfa_pending', true);
        $audit->record($user->id, 'platform.password.required_reset_completed', PlatformUser::class, (string) $user->id);

        return redirect()->route($user->mfa_confirmed_at ? 'platform.mfa.challenge' : 'platform.mfa.setup');
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
