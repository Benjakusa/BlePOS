<?php

namespace App\Services;

use App\Models\Shop;

class ShopContext
{
    private static ?Shop $currentShop = null;

    private static bool $allShopsAllowed = false;

    public static function setShop(?Shop $shop): void
    {
        self::$currentShop = $shop;
        self::$allShopsAllowed = false;
    }

    public static function getShop(): ?Shop
    {
        return self::$currentShop;
    }

    public static function getShopId(): ?int
    {
        return self::$currentShop?->id;
    }

    public static function hasShop(): bool
    {
        return self::$currentShop !== null;
    }

    public static function clear(): void
    {
        self::$currentShop = null;
        self::$allShopsAllowed = false;
    }

    public static function allowAllShops(): void
    {
        self::$currentShop = null;
        self::$allShopsAllowed = true;
    }

    public static function allowsAllShops(): bool
    {
        return self::$allShopsAllowed;
    }
}
