<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::getTenant();

        abort_unless($tenant !== null, 403, 'Tenant context could not be resolved.');

        abort_if($tenant->isSuspended(), 403, 'This business account is currently suspended.');

        if ($request->user()) {
            abort_if($request->user()->status !== 'active', 403, 'This tenant user account is inactive.');
            abort_unless(
                (int) $request->user()->tenant_id === (int) $tenant->id,
                403,
                'Cross-tenant access is strictly prohibited.',
            );
        }

        return $next($request);
    }
}
