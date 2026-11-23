<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // 1. Resolve from authenticated user
        if ($request->user() && $request->user()->tenant_id) {
            $tenant = Tenant::query()->find($request->user()->tenant_id);
        }

        // 2. Resolve from custom domain or subdomain
        if (! $tenant) {
            $host = $request->getHost();
            $tenantDomain = TenantDomain::query()->where('domain', $host)->first();
            if ($tenantDomain) {
                $tenant = $tenantDomain->tenant;
            } else {
                // Check subdomain e.g. acme.blepos.co.ke
                $parts = explode('.', $host);
                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                    $tenant = Tenant::query()->where('slug', $subdomain)->first();
                }
            }
        }

        // 3. Optional header override for internal/testing/API requests
        if (! $tenant && $request->hasHeader('X-Tenant-Code')) {
            $tenant = Tenant::query()->where('code', $request->header('X-Tenant-Code'))->first();
        }

        if ($tenant) {
            TenantContext::setTenant($tenant);
        }

        try {
            return $next($request);
        } finally {
            // Note: Context is preserved during request execution and can be cleared on terminate
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
    }
}
