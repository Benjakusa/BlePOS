<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Complete a POS sale transaction with items, stock deduction, and payments.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $payments
     */
    public function createSale(
        Shop $shop,
        Register $register,
        User $user,
        array $items,
        array $payments,
        ?CashierShift $shift = null,
        ?string $notes = null
    ): Sale {
        return DB::transaction(function () use (
            $shop,
            $register,
            $user,
            $items,
            $payments,
            $shift,
            $notes
        ): Sale {
            abort_if(empty($items), 422, 'Sale must contain at least one item.');
            abort_if(empty($payments), 422, 'Sale must contain at least one payment.');

            $subtotal = '0.00';
            $discountTotal = '0.00';
            $taxTotal = '0.00';
            $grandTotal = '0.00';

            $saleNumber = 'SL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            $sale = Sale::query()->create([
                'tenant_id' => $shop->tenant_id,
                'shop_id' => $shop->id,
                'register_id' => $register->id,
                'cashier_shift_id' => $shift?->id,
                'user_id' => $user->id,
                'sale_number' => $saleNumber,
                'status' => 'completed',
                'subtotal' => 0.00,
                'discount_total' => 0.00,
                'tax_total' => 0.00,
                'grand_total' => 0.00,
                'paid_total' => 0.00,
                'change_due' => 0.00,
                'payment_status' => 'paid',
                'notes' => $notes,
            ]);

            foreach ($items as $itemData) {
                $product = Product::query()->findOrFail($itemData['product_id']);
                $qty = (float) ($itemData['quantity'] ?? 1.0);
                $unitPrice = (string) ($itemData['unit_price'] ?? $product->selling_price);
                $itemDiscount = (string) ($itemData['discount'] ?? '0.00');

                $lineSub = bcsub(bcmul($unitPrice, (string) $qty, 4), $itemDiscount, 2);

                // Tax calculation
                $taxRate = (string) $product->tax_rate;
                if ($product->tax_type === 'inclusive' && (float) $taxRate > 0) {
                    // Tax included: tax = lineSub - (lineSub / (1 + rate/100))
                    $divisor = bcadd('1', bcdiv($taxRate, '100', 4), 4);
                    $net = bcdiv($lineSub, $divisor, 2);
                    $taxAmount = bcsub($lineSub, $net, 2);
                    $lineTotal = $lineSub;
                } elseif ($product->tax_type === 'exclusive' && (float) $taxRate > 0) {
                    $taxAmount = bcmul($lineSub, bcdiv($taxRate, '100', 4), 2);
                    $lineTotal = bcadd($lineSub, $taxAmount, 2);
                } else {
                    $taxAmount = '0.00';
                    $lineTotal = $lineSub;
                }

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_cost' => $product->cost_price,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $itemDiscount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total_price' => $lineTotal,
                ]);

                // Stock deduction
                if ($product->track_stock) {
                    $this->inventory->adjustStock(
                        product: $product,
                        shop: $shop,
                        quantityDelta: -abs($qty),
                        type: 'sale',
                        user: $user,
                        reason: "POS Sale #{$saleNumber}",
                        referenceType: 'Sale',
                        referenceId: (string) $sale->id
                    );
                }

                $subtotal = bcadd($subtotal, $lineSub, 2);
                $discountTotal = bcadd($discountTotal, $itemDiscount, 2);
                $taxTotal = bcadd($taxTotal, $taxAmount, 2);
                $grandTotal = bcadd($grandTotal, $lineTotal, 2);
            }

            // Record Payments
            $paidTotal = '0.00';
            $cashAmount = '0.00';
            $mpesaAmount = '0.00';
            $cardAmount = '0.00';

            foreach ($payments as $paymentData) {
                $amount = (string) $paymentData['amount'];
                $method = $paymentData['method'];

                SalePayment::query()->create([
                    'sale_id' => $sale->id,
                    'payment_method' => $method,
                    'amount' => $amount,
                    'reference' => $paymentData['reference'] ?? null,
                    'metadata' => $paymentData['metadata'] ?? null,
                ]);

                $paidTotal = bcadd($paidTotal, $amount, 2);

                if ($method === 'cash') {
                    $cashAmount = bcadd($cashAmount, $amount, 2);
                } elseif ($method === 'mpesa') {
                    $mpesaAmount = bcadd($mpesaAmount, $amount, 2);
                } elseif ($method === 'card') {
                    $cardAmount = bcadd($cardAmount, $amount, 2);
                }
            }

            $changeDue = bccomp($paidTotal, $grandTotal, 2) > 0 ? bcsub($paidTotal, $grandTotal, 2) : '0.00';

            // Actual cash received into drawer is payment minus change
            $netCash = bccomp($cashAmount, $changeDue, 2) >= 0 ? bcsub($cashAmount, $changeDue, 2) : $cashAmount;

            $sale->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'change_due' => $changeDue,
            ]);

            // Update Cashier Shift if attached
            if ($shift && $shift->isOpen()) {
                $shift->update([
                    'cash_sales' => bcadd((string) $shift->cash_sales, $netCash, 2),
                    'mpesa_sales' => bcadd((string) $shift->mpesa_sales, $mpesaAmount, 2),
                    'card_sales' => bcadd((string) $shift->card_sales, $cardAmount, 2),
                ]);
            }

            return $sale;
        });
    }

    /**
     * Void a completed sale and reverse stock deductions.
     */
    public function voidSale(Sale $sale, User $user, string $reason): Sale
    {
        return DB::transaction(function () use ($sale, $user, $reason): Sale {
            abort_unless($sale->isCompleted(), 422, 'Only completed sales can be voided.');

            // Reverse stock for all items
            foreach ($sale->items as $item) {
                if ($item->product->track_stock) {
                    $this->inventory->adjustStock(
                        product: $item->product,
                        shop: $sale->shop,
                        quantityDelta: (float) $item->quantity,
                        type: 'void',
                        user: $user,
                        reason: "Void Sale #{$sale->sale_number}: {$reason}",
                        referenceType: 'Sale',
                        referenceId: (string) $sale->id
                    );
                }
            }

            $sale->update([
                'status' => 'voided',
                'notes' => trim(($sale->notes ?? '')."\nVoid reason: {$reason}"),
            ]);

            return $sale;
        });
    }
}
