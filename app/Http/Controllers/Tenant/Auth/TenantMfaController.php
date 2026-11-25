<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Services\PlatformTotp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantMfaController extends Controller
{
    public function setup(Request $request, PlatformTotp $totp): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);
        if ($user->mfa_confirmed_at) {
            return redirect()->route('tenant.mfa.challenge');
        }
        if (! $user->totp_secret) {
            $user->forceFill(['totp_secret' => $totp->newSecret()])->save();
        }
        $uri = $totp->provisioningUri($user->totp_secret, $user->email);

        return view('tenant.auth.mfa-setup', ['secret' => $user->totp_secret, 'qrSvg' => base64_encode($totp->qrSvg($uri))]);
    }

    public function confirmSetup(Request $request, PlatformTotp $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        abort_unless($user && $user->totp_secret && $totp->verify($user->totp_secret, $request->string('code')), 422, 'Authenticator code is invalid.');
        $codes = $totp->recoveryCodes();
        $user->forceFill([
            'mfa_confirmed_at' => now(),
            'recovery_code_hashes' => array_map(fn (string $code) => Hash::make($code), $codes),
        ])->save();
        $request->session()->regenerate();
        $request->session()->put('tenant_mfa_verified_at', now()->timestamp);
        $request->session()->flash('tenant_recovery_codes', $codes);

        return redirect()->route('tenant.dashboard')->with('status', 'MFA enabled. Save your one-time recovery codes now.');
    }

    public function challenge(): View
    {
        return view('tenant.auth.mfa-challenge');
    }

    public function verify(Request $request, PlatformTotp $totp): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:32']]);
        $user = $request->user();
        abort_unless($user, 403);
        if ($user->totp_secret && $totp->verify($user->totp_secret, $request->string('code'))) {
            $request->session()->regenerate();
            $request->session()->put('tenant_mfa_verified_at', now()->timestamp);

            return redirect()->intended(route('tenant.dashboard'));
        }
        $hashes = $user->recovery_code_hashes ?? [];
        foreach ($hashes as $index => $hash) {
            if (Hash::check($request->string('code'), $hash)) {
                unset($hashes[$index]);
                $user->forceFill(['recovery_code_hashes' => array_values($hashes)])->save();
                $request->session()->regenerate();
                $request->session()->put('tenant_mfa_verified_at', now()->timestamp);

                return redirect()->intended(route('tenant.dashboard'))->with('warning', 'A one-time recovery code was used. Generate new codes through your administrator.');
            }
        }

        return back()->withErrors(['code' => 'Authenticator or recovery code is invalid.']);
    }
}
