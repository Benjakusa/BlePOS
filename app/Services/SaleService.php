<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\PharmacyBatch;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        protected InventoryService $inventory,
        protected AccountingService $accounting,
        protected PharmacyService $pharmacy
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
        ?string $notes = null,
        ?int $diningTableId = null,
        string $idempotencyKey = ''
    ): Sale {
        abort_if(trim($idempotencyKey) === '' || strlen($idempotencyKey) > 100, 422, 'A valid idempotency key is required for every sale.');

        $idempotencyFingerprint = hash('sha256', json_encode([
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_id' => $shift?->id,
            'dining_table_id' => $diningTableId,
            'items' => $items,
            'payments' => $payments,
            'notes' => $notes,
        ], JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use (
                $shop,
                $register,
                $user,
                $items,
                $payments,
                $shift,
                $notes,
                $diningTableId,
                $idempotencyKey,
                $idempotencyFingerprint
            ): Sale {
                $existing = Sale::withoutGlobalScopes()->where('tenant_id', $shop->tenant_id)
                    ->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    abort_unless(
                        hash_equals((string) $existing->idempotency_fingerprint, $idempotencyFingerprint),
                        409,
                        'This idempotency key was already used for a different checkout.'
                    );

                    return $existing;
                }
                abort_if(empty($items), 422, 'Sale must contain at least one item.');
                abort_if(empty($payments), 422, 'Sale must contain at least one payment.');

                $subtotal = '0.00';
                $discountTotal = '0.00';
                $taxTotal = '0.00';
                $grandTotal = '0.00';
                $pharmacyDispensings = [];

                $saleNumber = 'SL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

                $sale = Sale::query()->create([
                    'tenant_id' => $shop->tenant_id,
                    'shop_id' => $shop->id,
                    'register_id' => $register->id,
                    'cashier_shift_id' => $shift?->id,
                    'dining_table_id' => $diningTableId,
                    'user_id' => $user->id,
                    'sale_number' => $saleNumber,
                    'status' => 'completed',
                    'subtotal' => 0.00,
                    'discount_total' => 0.00,
                    'tax_total' => 0.00,
                    'grand_total' => 0.00,
                    'paid_total' => 0.00,
                    'change_due' => 0.00,
                    'payment_status' => 'pending',
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_fingerprint' => $idempotencyFingerprint,
                    'notes' => $notes,
                ]);

                foreach ($items as $itemData) {
                    $product = Product::query()->where('tenant_id', $shop->tenant_id)
                        ->where('is_active', true)->lockForUpdate()->findOrFail($itemData['product_id']);
                    abort_if(
                        $product->is_reusable && (! $product->track_stock || (int) $product->availability_period_hours < 1),
                        422,
                        'Reusable products must track stock and have a positive availability period.'
                    );
                    $qty = (float) ($itemData['quantity'] ?? 1.0);
                    if ($shop->tenant?->industry === 'pharmacy') {
                        abort_if(
                            ! empty($itemData['pharmacist_user_id']) && (int) $itemData['pharmacist_user_id'] !== (int) $user->id,
                            403,
                            'The dispensing pharmacist must authenticate as the cashier performing this checkout.'
                        );
                        $pharmacist = ! empty($itemData['pharmacist_user_id'])
                            ? $user
                            : null;
                        $batch = $this->pharmacy->validateItemForSale(
                            shop: $shop,
                            product: $product,
                            batchId: isset($itemData['batch_id']) ? (int) $itemData['batch_id'] : null,
                            authorizingPharmacist: $pharmacist,
                            quantity: $qty,
                            prescriptionId: isset($itemData['prescription_id']) ? (int) $itemData['prescription_id'] : null,
                        );
                        $pharmacyDispensings[] = [$product, $qty, $batch, $itemData, $pharmacist];
                    }
                    $unitPrice = (string) $product->selling_price;
                    $itemDiscount = (string) ($itemData['discount'] ?? '0.00');
                    $discountEnabled = $user->hasPermission('pos.discount.apply') || $user->hasPermission('pos.sale.discount');
                    $discountOverride = $user->hasPermission('pos.discount.override');
                    abort_if(bccomp($itemDiscount, '0.00', 2) > 0 && ! $discountEnabled && ! $discountOverride, 403, 'Discount permission required.');
                    $lineGross = bcmul($unitPrice, (string) $qty, 2);
                    abort_if(bccomp($itemDiscount, $lineGross, 2) > 0, 422, 'Discount cannot exceed item price.');
                    if (bccomp($itemDiscount, '0.00', 2) > 0 && ! $discountOverride) {
                        $discountCapPercent = number_format((float) config('pos.max_discount_percent', 10), 2, '.', '');
                        $discountCap = bcdiv(bcmul($lineGross, $discountCapPercent, 4), '100.00', 2);
                        abort_if(bccomp($itemDiscount, $discountCap, 2) > 0, 422, 'Discount exceeds the cashier discount limit. Supervisor approval is required.');
                    }

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
                        'availability_release_at' => $product->is_reusable && $product->track_stock
                            ? now()->addHours((int) $product->availability_period_hours)
                            : null,
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

                foreach ($pharmacyDispensings as [$product, $qty, $batch, $itemData, $pharmacist]) {
                    if ($batch instanceof PharmacyBatch) {
                        $this->pharmacy->recordSaleDispensing(
                            shop: $shop,
                            user: $user,
                            product: $product,
                            quantity: $qty,
                            batch: $batch,
                            sale: $sale,
                            prescriptionId: isset($itemData['prescription_id']) ? (int) $itemData['prescription_id'] : null,
                            verifiedBy: $pharmacist,
                        );
                    }
                }

                // Record Payments
                $paidTotal = '0.00';
                $creditTotal = '0.00';
                $cashAmount = '0.00';
                $mpesaAmount = '0.00';
                $cardAmount = '0.00';

                foreach ($payments as $paymentData) {
                    $amount = (string) $paymentData['amount'];
                    $method = $paymentData['method'];

                    if ($method === 'credit') {
                        abort_if(! $user->hasPermission('pos.sale.credit'), 403, 'Credit sale permission required.');
                        $creditTotal = bcadd($creditTotal, $amount, 2);
                        SalePayment::query()->create([
                            'sale_id' => $sale->id,
                            'payment_method' => 'credit',
                            'amount' => $amount,
                            'reference' => $paymentData['reference'] ?? null,
                            'metadata' => ['status' => 'outstanding'],
                        ]);

                        continue;
                    }
                    abort_if(in_array($method, ['mpesa', 'card', 'cheque'], true), 422, 'This payment method requires a verified provider settlement before checkout. Cash or authorized credit is available.');
                    abort_if(bccomp($amount, '0.00', 2) <= 0, 422, 'Payment amount must be positive.');

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

                $settledTotal = bcadd($paidTotal, $creditTotal, 2);
                $hasCredit = bccomp($creditTotal, '0.00', 2) > 0;
                abort_if($hasCredit
                    ? bccomp($settledTotal, $grandTotal, 2) !== 0
                    : bccomp($paidTotal, $grandTotal, 2) < 0,
                    422,
                    'Tender must settle the total or explicitly record the exact balance as authorized credit.');

                $changeDue = bccomp($paidTotal, $grandTotal, 2) > 0 ? bcsub($paidTotal, $grandTotal, 2) : '0.00';
                $paidTotalAfterChange = bcsub($paidTotal, $changeDue, 2);

                // Actual cash received into drawer is payment minus change
                $netCash = bccomp($cashAmount, $changeDue, 2) >= 0 ? bcsub($cashAmount, $changeDue, 2) : $cashAmount;

                $sale->update([
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => $grandTotal,
                    'paid_total' => $paidTotalAfterChange,
                    'change_due' => $changeDue,
                    'payment_status' => bccomp($creditTotal, '0.00', 2) > 0 ? 'credit' : 'paid',
                ]);

                // Update Cashier Shift if attached
                if ($shift && $shift->isOpen()) {
                    $shift->update([
                        'cash_sales' => bcadd((string) $shift->cash_sales, $netCash, 2),
                        'mpesa_sales' => bcadd((string) $shift->mpesa_sales, $mpesaAmount, 2),
                        'card_sales' => bcadd((string) $shift->card_sales, $cardAmount, 2),
                    ]);
                }

                // Record double-entry general ledger journal entry
                $this->accounting->recordSaleJournal($sale);
                app(TenantFinancialAuditRecorder::class)->record(
                    (int) $sale->tenant_id, (int) $sale->shop_id, (int) $user->id,
                    Sale::class, $sale->id, 'sale.created', null,
                    ['status' => $sale->status, 'grand_total' => $sale->grand_total, 'paid_total' => $sale->paid_total, 'payment_status' => $sale->payment_status]
                );

                return $sale;
            });
        } catch (QueryException $exception) {
            $message = strtolower($exception->getMessage());
            if (str_contains($message, 'unique') || str_contains($message, 'duplicate')) {
                $existing = Sale::withoutGlobalScopes()->where('tenant_id', $shop->tenant_id)
                    ->where('idempotency_key', $idempotencyKey)->first();
                if ($existing && hash_equals((string) $existing->idempotency_fingerprint, $idempotencyFingerprint)) {
                    return $existing;
                }
            }

            throw $exception;
        }
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
            app(TenantFinancialAuditRecorder::class)->record(
                (int) $sale->tenant_id, (int) $sale->shop_id, (int) $user->id,
                Sale::class, $sale->id, 'sale.voided', ['status' => 'completed', 'grand_total' => $sale->grand_total],
                ['status' => 'voided', 'grand_total' => $sale->grand_total]
            );

            // Reverse double-entry general ledger journal entry
            $this->accounting->voidSaleJournal($sale, $user, $reason);

            return $sale;
        });
    }
}
