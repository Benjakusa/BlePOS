<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(TenantContext::hasTenant(), 404, 'Tenant context could not be resolved.');

        return $next($request);
    }
}
