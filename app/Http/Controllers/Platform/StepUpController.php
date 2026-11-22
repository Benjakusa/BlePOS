<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformTotp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StepUpController extends Controller
{
    public function create(): View
    {
        return view('platform.auth.step-up');
    }

    public function store(Request $request, PlatformTotp $totp, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate(['password' => ['required', 'string'], 'code' => ['required', 'digits:6']]);
        $user = $request->user('platform');

        if (! $user || ! Hash::check($validated['password'], $user->password) || ! $user->totp_secret || ! $totp->verify($user->totp_secret, $validated['code'])) {
            return back()->withErrors(['password' => 'The password or authenticator code is invalid.']);
        }

        $request->session()->put('platform_step_up_at', now()->timestamp);
        $audit->record($user->id, 'platform.step_up.succeeded', get_class($user), (string) $user->id);

        return redirect()->intended(route('platform.dashboard'));
    }
}
