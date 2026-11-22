<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformStepUp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $verifiedAt = $request->session()->get('platform_step_up_at');
        if (! is_int($verifiedAt) || Carbon::createFromTimestamp($verifiedAt)->lt(now()->subMinutes(5))) {
            return redirect()->route('platform.step-up');
        }

        return $next($request);
    }
}
