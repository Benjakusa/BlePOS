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
        $preserveTestAccess = app()->runningUnitTests() && ShopContext::allowsAllShops();
        ShopContext::clear();
        $tenantId = TenantContext::getTenantId();
        abort_unless($tenantId !== null, 403, 'Tenant context is required before shop evaluation.');

        $shop = $request->route('shop');

        if (! ($shop instanceof Shop) && is_numeric($shop)) {
            $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($shop);
        }

        if (! $shop && $request->hasHeader('X-Shop-Id')) {
            $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($request->header('X-Shop-Id'));
        }

        if (! $shop && $request->hasHeader('X-Shop-Code')) {
            $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('code', $request->header('X-Shop-Code'))->first();
        }

        if ($request->input('shop_id') === 'all' && $request->user()?->hasRole('owner')) {
            ShopContext::allowAllShops();
            if ($request->hasSession()) {
                $request->session()->forget('active_shop_id');
            }
            $shop = null;
        } elseif (! $shop && $request->filled('shop_id')) {
            $shopInput = $request->input('shop_id');
            if (is_numeric($shopInput)) {
                $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($shopInput);
            }
        }

        if (! $shop && $request->hasSession() && $request->session()->has('active_shop_id')) {
            $sessionShopId = $request->session()->get('active_shop_id');
            if (is_numeric($sessionShopId)) {
                $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($sessionShopId);
            }
        }

        if (! $shop && $request->user()) {
            $assignedShops = $request->user()->shops()->withoutGlobalScopes()->where('shops.tenant_id', $tenantId);
            if ($assignedShops->count() === 1) {
                $shop = $assignedShops->first();
            }
        }

        if ($shop) {
            abort_unless((int) $shop->tenant_id === (int) $tenantId, 403, 'Shop does not belong to the active business entity.');

            if ($request->user()) {
                abort_unless($request->user()->canAccessShop($shop), 403, 'User is not authorized for this shop location.');
            }

            ShopContext::setShop($shop);

            if ($request->hasSession()) {
                $request->session()->put('active_shop_id', $shop->id);
            }
        }

        try {
            return $next($request);
        } finally {
            ShopContext::clear();
            if ($preserveTestAccess) {
                ShopContext::allowAllShops();
            }
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        ShopContext::clear();
    }
}
