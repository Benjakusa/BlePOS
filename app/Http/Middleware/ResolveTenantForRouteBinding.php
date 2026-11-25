<?php

namespace App\Http\Middleware;

use App\Services\ShopContext;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Resolve tenant scope before route binding, then clear static context per request. */
class ResolveTenantForRouteBinding
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('tenant') || $request->is('tenant/*')
            || $request->is('platform-console') || $request->is('platform-console/*')) {
            return app(ResolveTenantContext::class)->handle($request, $next);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
        ShopContext::clear();
    }
}
