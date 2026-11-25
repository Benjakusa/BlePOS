<?php

namespace App\Support;

use App\Services\ShopContext;
use App\Services\TenantContext;

/** Explicit all-tenant scope for trusted database seeding only. */
class SeederScopeContext
{
    public function __construct()
    {
        TenantContext::allowAllTenants();
        ShopContext::allowAllShops();
    }

    public function __destruct()
    {
        TenantContext::clear();
        ShopContext::clear();
    }
}
