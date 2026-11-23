<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlatformHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('platform.security.force_https') && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri())->setStatusCode(Response::HTTP_PERMANENTLY_REDIRECT);
        }

        return $next($request);
    }
}
