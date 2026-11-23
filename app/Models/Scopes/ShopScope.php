<?php

namespace App\Models\Scopes;

use App\Services\ShopContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (ShopContext::hasShop()) {
            $builder->where($model->qualifyColumn('shop_id'), '=', ShopContext::getShopId());
        }
    }
}
