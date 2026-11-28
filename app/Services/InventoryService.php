<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleStockReservation;
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
            if ($quantityDelta < 0 && $balanceAfter + 0.00001 < (float) ($stock?->reserved_quantity ?? 0)) {
                throw ValidationException::withMessages([
                    'stock' => 'This stock is reserved for another pending M-Pesa checkout.',
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

    /** Reserve tracked stock for a pending POS checkout in a stable product order. */
    public function reserveForPendingSale(Sale $sale, array $items, ?User $user = null): void
    {
        DB::transaction(function () use ($sale, $items, $user): void {
            $quantities = [];
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = number_format((float) $item['quantity'], 4, '.', '');
                $quantities[$productId] = isset($quantities[$productId])
                    ? bcadd($quantities[$productId], $quantity, 4)
                    : $quantity;
            }
            ksort($quantities, SORT_NUMERIC);

            foreach ($quantities as $productId => $quantity) {
                $product = Product::withoutGlobalScopes()
                    ->where('tenant_id', $sale->tenant_id)
                    ->findOrFail($productId);
                if (! $product->track_stock) {
                    continue;
                }

                $stock = InventoryStock::withoutGlobalScopes()
                    ->where('tenant_id', $sale->tenant_id)
                    ->where('shop_id', $sale->shop_id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
                $reservation = SaleStockReservation::query()
                    ->where('sale_id', $sale->id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();
                if ($reservation?->status === 'reserved') {
                    $reservation->update(['expires_at' => now()->addMinutes(15)]);

                    continue;
                }
                if (! $stock) {
                    throw ValidationException::withMessages(['stock' => "Insufficient stock for {$product->name}."]);
                }
                $available = bcsub((string) $stock->quantity, (string) $stock->reserved_quantity, 4);
                if (bccomp($available, $quantity, 4) < 0) {
                    throw ValidationException::withMessages(['stock' => "Insufficient available stock for {$product->name}."]);
                }

                $stock->reserved_quantity = bcadd((string) $stock->reserved_quantity, $quantity, 4);
                $stock->save();
                if ($reservation) {
                    $reservation->update(['quantity' => $quantity, 'status' => 'reserved', 'expires_at' => now()->addMinutes(15)]);
                } else {
                    SaleStockReservation::query()->create([
                        'sale_id' => $sale->id,
                        'tenant_id' => $sale->tenant_id,
                        'shop_id' => $sale->shop_id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'status' => 'reserved',
                        'expires_at' => now()->addMinutes(15),
                    ]);
                }

                $movement = StockMovement::query()->create([
                    'tenant_id' => $sale->tenant_id,
                    'shop_id' => $sale->shop_id,
                    'product_id' => $productId,
                    'user_id' => $user?->id,
                    'type' => 'reserve',
                    'quantity' => '0.0000',
                    'balance_before' => $stock->quantity,
                    'balance_after' => $stock->quantity,
                    'unit_cost' => $product->cost_price,
                    'reference_type' => Sale::class,
                    'reference_id' => (string) $sale->id,
                    'reason' => "Reserved {$quantity} for pending M-Pesa sale {$sale->sale_number}",
                ]);
                app(TenantFinancialAuditRecorder::class)->record(
                    (int) $sale->tenant_id, (int) $sale->shop_id, $user?->id,
                    StockMovement::class, $movement->id, 'stock.reserved', null,
                    ['quantity' => $quantity, 'sale_id' => $sale->id]
                );
            }
        });
    }

    /** Release or consume all active reservations for a pending POS sale. */
    public function settlePendingSaleReservation(Sale $sale, string $outcome): void
    {
        if (! in_array($outcome, ['released', 'consumed'], true)) {
            throw new \InvalidArgumentException('Invalid stock reservation outcome.');
        }

        DB::transaction(function () use ($sale, $outcome): void {
            $reservations = SaleStockReservation::query()
                ->where('sale_id', $sale->id)
                ->where('status', 'reserved')
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();
            foreach ($reservations as $reservation) {
                $stock = InventoryStock::withoutGlobalScopes()
                    ->where('tenant_id', $sale->tenant_id)
                    ->where('shop_id', $sale->shop_id)
                    ->where('product_id', $reservation->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (bccomp((string) $stock->reserved_quantity, (string) $reservation->quantity, 4) < 0) {
                    throw new \DomainException('Pending sale stock reservation is inconsistent.');
                }
                $product = Product::withoutGlobalScopes()->findOrFail($reservation->product_id);
                $stock->reserved_quantity = bcsub((string) $stock->reserved_quantity, (string) $reservation->quantity, 4);
                $stock->save();
                $reservation->update(['status' => $outcome, 'expires_at' => null]);
                $movement = StockMovement::query()->create([
                    'tenant_id' => $sale->tenant_id,
                    'shop_id' => $sale->shop_id,
                    'product_id' => $reservation->product_id,
                    'type' => $outcome === 'consumed' ? 'reserve_consume' : 'reserve_release',
                    'quantity' => '0.0000',
                    'balance_before' => $stock->quantity,
                    'balance_after' => $stock->quantity,
                    'unit_cost' => $product->cost_price,
                    'reference_type' => Sale::class,
                    'reference_id' => (string) $sale->id,
                    'reason' => ucfirst($outcome).' reservation of {$reservation->quantity} for POS sale {$sale->sale_number}',
                ]);
                app(TenantFinancialAuditRecorder::class)->record(
                    (int) $sale->tenant_id, (int) $sale->shop_id, null,
                    StockMovement::class, $movement->id, 'stock.reservation_'.$outcome, null,
                    ['quantity' => $reservation->quantity, 'sale_id' => $sale->id]
                );
            }
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
