<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::hasTenant()) {
            $builder->where($model->qualifyColumn('tenant_id'), '=', TenantContext::getTenantId());
        } elseif (! TenantContext::allowsAllTenants()) {
            $session = request()->hasSession() ? request()->session() : null;
            $sessionUserId = $session?->get('login_web_'.sha1(SessionGuard::class));
            if (is_numeric($sessionUserId)) {
                $builder->where($model->qualifyColumn('tenant_id'), '=', DB::table('users')
                    ->select('tenant_id')->where('id', $sessionUserId));
            } else {
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
