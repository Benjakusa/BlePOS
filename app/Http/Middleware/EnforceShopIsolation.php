<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceShopIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = TenantContext::getTenantId();
        abort_unless($tenantId !== null, 403, 'Tenant context is required before shop evaluation.');

        $shop = $request->route('shop');

        if (! ($shop instanceof Shop) && is_numeric($shop)) {
            $shop = Shop::query()->find($shop);
        }

        if (! $shop && $request->hasHeader('X-Shop-Id')) {
            $shop = Shop::query()->find($request->header('X-Shop-Id'));
        }

        if (! $shop && $request->hasHeader('X-Shop-Code')) {
            $shop = Shop::query()->where('code', $request->header('X-Shop-Code'))->first();
        }

        if ($shop) {
            abort_unless((int) $shop->tenant_id === (int) $tenantId, 403, 'Shop does not belong to the active business entity.');

            if ($request->user()) {
                abort_unless($request->user()->canAccessShop($shop), 403, 'User is not authorized for this shop location.');
            }

            ShopContext::setShop($shop);
        }

        try {
            return $next($request);
        } finally {
            // Context cleared on terminate if needed
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        ShopContext::clear();
    }
}
