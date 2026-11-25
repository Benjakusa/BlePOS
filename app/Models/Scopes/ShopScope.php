<?php

namespace App\Models\Scopes;

use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (ShopContext::hasShop()) {
            $builder->where($model->qualifyColumn('shop_id'), '=', ShopContext::getShopId());
        } elseif (TenantContext::hasTenant()
            && request()->user()
            && (request()->user()->isTenantOwner() || request()->user()->hasPermission('shops.all.manage'))) {
            // Tenant-wide shop access is an explicit permission boundary; the
            // TenantScope on shop-owned models still limits the result set.
        } elseif (TenantContext::hasTenant()
            && request()->hasSession()
            && is_numeric(request()->session()->get('active_shop_id'))) {
            // Route model binding runs before shop-isolation route middleware.
            // The session value is written only after EnforceShopIsolation validates it.
            $builder->where($model->qualifyColumn('shop_id'), '=', request()->session()->get('active_shop_id'));
        } elseif (! ShopContext::allowsAllShops()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
