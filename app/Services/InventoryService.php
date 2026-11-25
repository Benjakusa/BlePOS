<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Adjust stock level for a product in a shop with an immutable movement record.
     */
    public function adjustStock(
        Product $product,
        Shop $shop,
        float $quantityDelta,
        string $type,
        ?User $user = null,
        ?string $reason = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $shop,
            $quantityDelta,
            $type,
            $user,
            $reason,
            $referenceType,
            $referenceId
        ): StockMovement {
            $stock = InventoryStock::withoutGlobalScopes()
                ->where('tenant_id', $shop->tenant_id)
                ->where('shop_id', $shop->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $balanceBefore = (float) ($stock?->quantity ?? 0.0);
            $balanceAfter = $balanceBefore + $quantityDelta;
            if ($type === 'sale' && $balanceAfter < -0.00001) {
                throw ValidationException::withMessages([
                    'stock' => 'Insufficient stock to complete this sale.',
                ]);
            }

            if ($stock) {
                $stock->update(['quantity' => $balanceAfter]);
            } else {
                $stock = InventoryStock::query()->create([
                    'tenant_id' => $shop->tenant_id,
                    'shop_id' => $shop->id,
                    'product_id' => $product->id,
                    'quantity' => $balanceAfter,
                ]);
            }

            $movement = StockMovement::query()->create([
                'tenant_id' => $shop->tenant_id,
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $quantityDelta,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'unit_cost' => $product->cost_price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
            ]);
            app(TenantFinancialAuditRecorder::class)->record(
                (int) $shop->tenant_id, (int) $shop->id, $user?->id,
                StockMovement::class, $movement->id, 'stock.'.$type,
                ['quantity' => $balanceBefore], ['quantity' => $balanceAfter, 'movement_id' => $movement->id]
            );

            return $movement;
        });
    }

    /**
     * Receive new stock batch.
     */
    public function receiveStock(
        Product $product,
        Shop $shop,
        float $quantity,
        ?User $user = null,
        ?string $referenceId = null
    ): StockMovement {
        return $this->adjustStock(
            product: $product,
            shop: $shop,
            quantityDelta: abs($quantity),
            type: 'receipt',
            user: $user,
            reason: 'Purchase stock receipt',
            referenceType: 'Purchase',
            referenceId: $referenceId
        );
    }

    /**
     * Get stock level of product in shop.
     */
    public function getStock(Product $product, Shop $shop): float
    {
        $stock = InventoryStock::withoutGlobalScopes()
            ->where('tenant_id', $shop->tenant_id)
            ->where('shop_id', $shop->id)
            ->where('product_id', $product->id)
            ->first();

        return (float) ($stock?->quantity ?? 0.0);
    }
}
