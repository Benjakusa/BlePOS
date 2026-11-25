<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $this->requiresMfa($user)) {
            return $next($request);
        }
        if ($request->routeIs('tenant.mfa.*')) {
            return $next($request);
        }
        if (! $user->mfa_confirmed_at) {
            return redirect()->route('tenant.mfa.setup');
        }
        $verifiedAt = (int) $request->session()->get('tenant_mfa_verified_at', 0);
        if ($verifiedAt <= 0 || $verifiedAt > now()->timestamp || now()->timestamp - $verifiedAt > 12 * 60 * 60) {
            $request->session()->forget('tenant_mfa_verified_at');

            return redirect()->route('tenant.mfa.challenge');
        }

        return $next($request);
    }

    private function requiresMfa(object $user): bool
    {
        return $user->isTenantOwner() || collect([
            'users.manage', 'catalog.products.manage', 'catalog.categories.manage',
            'inventory.adjust', 'accounting.view', 'tenant.billing.manage', 'shops.all.manage',
        ])->contains(fn (string $permission) => $user->hasPermission($permission));
    }
}
