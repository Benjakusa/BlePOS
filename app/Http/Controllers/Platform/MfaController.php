<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Notifications\PlatformNewLoginAlert;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformSecurityEvents;
use App\Services\PlatformTotp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MfaController extends Controller
{
    public function setup(Request $request, PlatformTotp $totp): View|RedirectResponse
    {
        $user = $request->user('platform');
        abort_unless($user instanceof PlatformUser && $request->session()->get('platform_mfa_pending') === true, 403);

        if ($user->mfa_confirmed_at) {
            return redirect()->route('platform.mfa.challenge');
        }

        if (! $user->totp_secret) {
            $user->forceFill(['totp_secret' => $totp->newSecret()])->save();
            $user->refresh();
        }

        $uri = $totp->provisioningUri($user->totp_secret, $user->email);

        return view('platform.auth.mfa-setup', [
            'secret' => $user->totp_secret,
            'qrSvg' => base64_encode($totp->qrSvg($uri)),
        ]);
    }

    public function confirmSetup(Request $request, PlatformTotp $totp, PlatformSecurityEvents $events, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user('platform');
        abort_unless($user instanceof PlatformUser && $request->session()->get('platform_mfa_pending') === true, 403);

        if (! $user->totp_secret || ! $totp->verify($user->totp_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'The authenticator code is invalid or expired.']);
        }

        $recoveryCodes = $totp->recoveryCodes();
        $user->forceFill([
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => array_map(fn (string $code): string => Hash::make($code), $recoveryCodes),
        ])->save();

        $audit->record($user->id, 'platform.mfa.enrolled', PlatformUser::class, (string) $user->id);
        $request->session()->flash('platform_recovery_codes', $recoveryCodes);

        return $this->completeAuthentication($request, $user, $events);
    }

    public function challenge(Request $request): View
    {
        abort_unless($request->user('platform') && $request->session()->get('platform_mfa_pending') === true, 403);

        return view('platform.auth.mfa-challenge');
    }

    public function verify(Request $request, PlatformTotp $totp, PlatformSecurityEvents $events, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $user = $request->user('platform');
        abort_unless($user instanceof PlatformUser && $request->session()->get('platform_mfa_pending') === true, 403);

        if ($user->mfa_confirmed_at && $user->totp_secret && $totp->verify($user->totp_secret, $validated['code'])) {
            $audit->record($user->id, 'platform.mfa.challenge_succeeded', PlatformUser::class, (string) $user->id);

            return $this->completeAuthentication($request, $user, $events);
        }

        if ($this->consumeRecoveryCode($user, $validated['code'])) {
            $user->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $user->forceFill(['totp_secret' => null, 'mfa_confirmed_at' => null, 'recovery_code_hashes' => null])->save();
            $events->record($request, 'platform.mfa.recovery_used', 'warning', $user->id);
            $audit->record($user->id, 'platform.mfa.recovery_used', PlatformUser::class, (string) $user->id);

            return redirect()->route('platform.mfa.setup')->with('status', 'Recovery code accepted. Enroll a new authenticator to continue.');
        }

        $events->record($request, 'platform.mfa.challenge_failed', 'warning', $user->id);

        return back()->withErrors(['code' => 'The authenticator or recovery code is invalid.']);
    }

    private function consumeRecoveryCode(PlatformUser $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = PlatformUser::query()->lockForUpdate()->find($user->id);
            $hashes = $lockedUser?->recovery_code_hashes ?? [];

            foreach ($hashes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($hashes[$index]);
                    $lockedUser->forceFill(['recovery_code_hashes' => array_values($hashes)])->save();

                    return true;
                }
            }

            return false;
        });
    }

    private function completeAuthentication(Request $request, PlatformUser $user, PlatformSecurityEvents $events): RedirectResponse
    {
        $now = now();
        $ipAddress = $request->ip();
        $userAgent = mb_substr((string) $request->userAgent(), 0, 1000);
        $previousSession = PlatformSession::query()
            ->where('platform_user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->where('last_activity_at', '>=', $now->copy()->subMinutes(30))
            ->orderByDesc('logged_in_at')
            ->first();
        $unfamiliarClient = $previousSession && (
            $previousSession->ip_address !== $ipAddress || $previousSession->user_agent !== $userAgent
        );

        $request->session()->regenerate();
        $request->session()->forget('platform_mfa_pending');
        $request->session()->put('platform_mfa_verified_at', $now->timestamp);
        $sessionId = $request->session()->getId();
        $hash = hash('sha256', $sessionId);

        PlatformSession::query()->updateOrCreate(['session_hash' => $hash], [
            'platform_user_id' => $user->id,
            'session_identifier' => $sessionId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'logged_in_at' => $now,
            'last_activity_at' => $now,
            'expires_at' => $now->copy()->addHours(8),
            'revoked_at' => null,
        ]);
        $user->forceFill(['last_login_at' => $now])->save();
        if ($unfamiliarClient) {
            $events->record($request, 'platform.login.unfamiliar_client', 'warning', $user->id, [
                'previous_ip' => $previousSession->ip_address,
                'current_ip' => $ipAddress,
                'previous_user_agent' => $previousSession->user_agent,
                'current_user_agent' => $userAgent,
            ]);
            $user->notify(new PlatformNewLoginAlert($ipAddress, $userAgent, $now));
        }

        return redirect()->intended(route('platform.dashboard'));
    }
}
