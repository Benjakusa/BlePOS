<?php

namespace App\Http\Middleware;

use App\Models\PlatformSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformMfa
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('platform');
        if (! $user) {
            return redirect()->route('platform.login');
        }

        if (! $user->mfa_confirmed_at) {
            if ($request->session()->get('platform_mfa_pending') === true) {
                return redirect()->route('platform.mfa.setup');
            }

            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('platform.login');
        }

        if (! $request->session()->has('platform_mfa_verified_at')) {
            $sessionRecord = PlatformSession::query()
                ->where('platform_user_id', $user->id)
                ->where('session_hash', hash('sha256', $request->session()->getId()))
                ->whereNull('revoked_at')
                ->first();

            if ($sessionRecord) {
                $request->session()->put('platform_mfa_verified_at', $sessionRecord->logged_in_at->timestamp);
            } else {
                $request->session()->put('platform_mfa_pending', true);

                return redirect()->route('platform.mfa.challenge');
            }
        }

        return $next($request);
    }
}
