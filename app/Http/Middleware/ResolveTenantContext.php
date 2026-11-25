<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $preserveTestAccess = app()->runningUnitTests() && TenantContext::allowsAllTenants();
        $preserveTestShopAccess = app()->runningUnitTests() && ShopContext::allowsAllShops();
        TenantContext::clear();
        $tenant = null;

        // Tenant global scopes fail closed. Resolve the authenticated web
        // identity once by its server-side session id so this middleware can
        // establish the tenant before route model binding runs.
        $webGuard = Auth::guard('web');
        if (! $request->user() && $request->hasSession()) {
            $sessionUserId = $request->session()->get($webGuard->getName());
            if (is_numeric($sessionUserId)) {
                $sessionUser = User::withoutGlobalScopes()->find($sessionUserId);
                if ($sessionUser) {
                    $webGuard->setUser($sessionUser);
                    $request->setUserResolver(fn () => $sessionUser);
                }
            }
        }

        // 1. Resolve from authenticated user
        if ($request->user() && $request->user()->tenant_id) {
            $tenant = Tenant::withoutGlobalScopes()->find($request->user()->tenant_id);
        }

        // 2. Resolve from custom domain or subdomain
        if (! $tenant) {
            $host = $request->getHost();
            $tenantDomain = TenantDomain::withoutGlobalScopes()->where('domain', $host)->first();
            if ($tenantDomain) {
                $tenant = Tenant::withoutGlobalScopes()->find($tenantDomain->tenant_id);
            } else {
                // Check subdomain e.g. acme.blepos.co.ke
                $parts = explode('.', $host);
                if (count($parts) >= 3) {
                    $subdomain = $parts[0];
                    $tenant = Tenant::withoutGlobalScopes()->where('slug', $subdomain)->first();
                }
            }
        }

        // 3. Optional header override for internal/testing/API requests
        if (! $tenant && $request->hasHeader('X-Tenant-Code')) {
            $tenant = Tenant::withoutGlobalScopes()->where('code', $request->header('X-Tenant-Code'))->first();
        }

        if ($tenant) {
            TenantContext::setTenant($tenant);
        } elseif (Auth::guard('platform')->check()) {
            // The platform console is a trusted all-tenant control plane. Its
            // route bindings still need explicit, temporary cross-tenant scope.
            TenantContext::allowAllTenants();
            ShopContext::allowAllShops();
        }

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
            ShopContext::clear();
            if ($preserveTestAccess || app()->runningUnitTests()) {
                TenantContext::allowAllTenants();
            }
            if ($preserveTestShopAccess || app()->runningUnitTests()) {
                ShopContext::allowAllShops();
            }
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
    }
}
