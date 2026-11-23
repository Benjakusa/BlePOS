<?php

namespace App\Http\Middleware;

use App\Models\PlatformSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackPlatformSession
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

        $sessionId = $request->session()->getId();
        $record = PlatformSession::query()
            ->where('session_hash', hash('sha256', $sessionId))
            ->where('platform_user_id', $user->id)
            ->whereNull('revoked_at')
            ->first();
        $now = now();

        if (! $record || $record->expires_at->isPast() || $record->last_activity_at->lt($now->copy()->subMinutes(30))) {
            if ($record) {
                $record->update(['revoked_at' => $now]);
            }

            Auth::guard('platform')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('platform.login');
        }

        $record->update(['last_activity_at' => $now]);

        return $next($request);
    }
}
