<?php

namespace App\Services;

use App\Models\Shop;

class ShopContext
{
    private static ?Shop $currentShop = null;

    public static function setShop(?Shop $shop): void
    {
        self::$currentShop = $shop;
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
    }
}
