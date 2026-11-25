<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Services\InventoryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('inventory:release-reusable-products {--limit=500 : Maximum sale items to process per run}')]
#[Description('Restore stock for reusable products whose availability period has ended.')]
class ReleaseReusableProductStock extends Command
{
    public function handle(InventoryService $inventory): int
    {
        $now = now();
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $saleItemIds = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereNotNull('sale_items.availability_release_at')
            ->whereNull('sale_items.availability_released_at')
            ->where('sale_items.availability_release_at', '<=', $now)
            ->orderBy('sale_items.id')
            ->limit($limit)
            ->pluck('sale_items.id');

        $released = 0;
        foreach ($saleItemIds as $saleItemId) {
            DB::transaction(function () use ($saleItemId, $now, $inventory, &$released): void {
                $item = SaleItem::query()->whereKey($saleItemId)->lockForUpdate()->first();
                if (! $item || $item->availability_released_at || ! $item->availability_release_at?->lte($now)) {
                    return;
                }

                $sale = Sale::withoutGlobalScopes()->find($item->sale_id);
                if (! $sale || $sale->status !== 'completed') {
                    return;
                }

                $product = Product::withoutGlobalScopes()->find($item->product_id);
                $shop = Shop::withoutGlobalScopes()->find($sale->shop_id);
                if (! $product || ! $shop || ! $product->is_reusable || ! $product->track_stock) {
                    $item->forceFill(['availability_released_at' => $now])->save();

                    return;
                }

                $inventory->adjustStock(
                    product: $product,
                    shop: $shop,
                    quantityDelta: (float) $item->quantity,
                    type: 'reusable_return',
                    reason: "Reusable availability period ended for Sale #{$sale->sale_number}",
                    referenceType: 'SaleItem',
                    referenceId: (string) $item->id,
                );
                $item->forceFill(['availability_released_at' => $now])->save();
                $released++;
            });
        }

        $this->info("Released stock for {$released} reusable sale item(s).");

        return self::SUCCESS;
    }
}
