<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIndustry
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $industries): Response
    {
        $tenant = TenantContext::getTenant();
        $allowed = explode(',', $industries);

        abort_unless($tenant && in_array($tenant->industry, $allowed, true), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
