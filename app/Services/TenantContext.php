<?php

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $currentTenant = null;

    private static bool $allTenantsAllowed = false;

    public static function setTenant(?Tenant $tenant): void
    {
        self::$currentTenant = $tenant;
        self::$allTenantsAllowed = false;
    }

    public static function getTenant(): ?Tenant
    {
        return self::$currentTenant;
    }

    public static function getTenantId(): ?int
    {
        return self::$currentTenant?->id;
    }

    public static function hasTenant(): bool
    {
        return self::$currentTenant !== null;
    }

    public static function allowAllTenants(): void
    {
        self::$currentTenant = null;
        self::$allTenantsAllowed = true;
    }

    public static function allowsAllTenants(): bool
    {
        return self::$allTenantsAllowed;
    }

    public static function clear(): void
    {
        self::$currentTenant = null;
        self::$allTenantsAllowed = false;
    }
}
