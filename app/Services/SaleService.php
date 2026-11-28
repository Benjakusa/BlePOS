<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\MpesaTransaction;
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
        string $idempotencyKey = '',
        ?MpesaTransaction $verifiedMpesaTransaction = null
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
                $idempotencyFingerprint,
                $verifiedMpesaTransaction
            ): Sale {
                $existing = Sale::withoutGlobalScopes()->where('tenant_id', $shop->tenant_id)
                    ->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    abort_unless(
                        hash_equals((string) $existing->idempotency_fingerprint, $idempotencyFingerprint),
                        409,
                        'This idempotency key was already used for a different checkout.'
                    );

                    if ($existing->status === 'completed') {
                        return $existing;
                    }
                    abort_unless($existing->status === 'pending_payment', 409, 'This checkout is not awaiting M-Pesa settlement.');
                }
                abort_if(empty($items), 422, 'Sale must contain at least one item.');
                abort_if(empty($payments), 422, 'Sale must contain at least one payment.');

                $subtotal = '0.00';
                $discountTotal = '0.00';
                $taxTotal = '0.00';
                $grandTotal = '0.00';
                $pharmacyDispensings = [];

                $saleNumber = 'SL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

                $hasMpesa = collect($payments)->contains(fn ($payment) => ($payment['method'] ?? null) === 'mpesa');
                if ($hasMpesa) {
                    abort_unless(
                        $existing !== null
                        && $verifiedMpesaTransaction?->status === 'confirmed'
                        && (int) $verifiedMpesaTransaction->sale_id === (int) $existing->id
                        && (int) $verifiedMpesaTransaction->tenant_id === (int) $shop->tenant_id
                        && (int) $verifiedMpesaTransaction->shop_id === (int) $shop->id,
                        403,
                        'M-Pesa sales can only be completed from their verified callback.'
                    );
                }

                $sale = $existing ?? Sale::query()->create([
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
                    abort_if(in_array($method, ['card', 'cheque'], true), 422, 'This payment method requires a verified provider settlement before checkout.');
                    abort_if($method === 'mpesa' && ! $hasMpesa, 422, 'M-Pesa settlement is not verified.');
                    abort_if(bccomp($amount, '0.00', 2) <= 0, 422, 'Payment amount must be positive.');

                    SalePayment::query()->create([
                        'sale_id' => $sale->id,
                        'payment_method' => $method,
                        'amount' => $amount,
                        'reference' => $method === 'mpesa'
                            ? $verifiedMpesaTransaction?->provider_receipt
                            : ($paymentData['reference'] ?? null),
                        'metadata' => $method === 'mpesa'
                            ? ['mpesa_transaction_id' => $verifiedMpesaTransaction?->id]
                            : ($paymentData['metadata'] ?? null),
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
                    'status' => 'completed',
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => $grandTotal,
                    'paid_total' => $paidTotalAfterChange,
                    'change_due' => $changeDue,
                    'payment_status' => bccomp($creditTotal, '0.00', 2) > 0 ? 'credit' : 'paid',
                    'pending_checkout_data' => null,
                ]);

                // Update Cashier Shift if attached
                if ($shift) {
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

    /** Create a server-priced pending sale before sending the customer's STK prompt. */
    public function createPendingMpesaSale(
        Shop $shop,
        Register $register,
        User $user,
        array $items,
        CashierShift $shift,
        ?string $notes,
        ?int $diningTableId,
        string $idempotencyKey
    ): Sale {
        abort_if(trim($idempotencyKey) === '' || strlen($idempotencyKey) > 100, 422, 'A valid idempotency key is required for every sale.');
        abort_if(empty($items), 422, 'Sale must contain at least one item.');
        $totals = $this->previewMpesaTotal($shop, $user, $items);
        $grandTotal = $totals['grand_total'];
        abort_if(bccomp($grandTotal, number_format((float) ceil((float) $grandTotal), 2, '.', ''), 2) !== 0, 422, 'M-Pesa STK sales must total a whole shilling. Adjust the cart or use another payment method.');

        $payments = [['method' => 'mpesa', 'amount' => $grandTotal, 'reference' => null]];
        $fingerprint = hash('sha256', json_encode([
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'dining_table_id' => $diningTableId,
            'items' => $items,
            'payments' => $payments,
            'notes' => $notes,
        ], JSON_THROW_ON_ERROR));
        $payload = [
            'items' => $items,
            'payments' => $payments,
            'notes' => $notes,
            'dining_table_id' => $diningTableId,
        ];

        return DB::transaction(function () use ($shop, $register, $user, $shift, $items, $notes, $diningTableId, $idempotencyKey, $fingerprint, $payload, $totals): Sale {
            $existing = Sale::withoutGlobalScopes()->where('tenant_id', $shop->tenant_id)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409, 'This idempotency key was already used for a different checkout.');
                abort_unless($existing->status === 'pending_payment', 409, 'This checkout has already been completed or cancelled.');
                $sale = $existing;
            } else {
                $sale = Sale::query()->create([
                    'tenant_id' => $shop->tenant_id,
                    'shop_id' => $shop->id,
                    'register_id' => $register->id,
                    'cashier_shift_id' => $shift->id,
                    'dining_table_id' => $diningTableId,
                    'user_id' => $user->id,
                    'sale_number' => 'SL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                    'status' => 'pending_payment',
                    'subtotal' => $totals['subtotal'],
                    'discount_total' => $totals['discount_total'],
                    'tax_total' => $totals['tax_total'],
                    'grand_total' => $totals['grand_total'],
                    'paid_total' => '0.00',
                    'change_due' => '0.00',
                    'payment_status' => 'pending',
                    'idempotency_key' => $idempotencyKey,
                    'idempotency_fingerprint' => $fingerprint,
                    'pending_checkout_data' => $payload,
                    'notes' => $notes,
                ]);
            }

            $this->inventory->reserveForPendingSale($sale, $items, $user);

            return $sale;
        });
    }

    public function releasePendingMpesaStock(Sale $sale): void
    {
        $this->inventory->settlePendingSaleReservation($sale, 'released');
    }

    /** Finalize a pending sale only from its persisted, confirmed M-Pesa transaction. */
    public function finalizeVerifiedMpesaSale(MpesaTransaction $transaction): Sale
    {
        return DB::transaction(function () use ($transaction): Sale {
            $lockedTransaction = MpesaTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            abort_unless($lockedTransaction->status === 'confirmed' && $lockedTransaction->sale_id, 409, 'Confirmed POS M-Pesa transaction is required.');
            $sale = Sale::withoutGlobalScopes()->lockForUpdate()->findOrFail($lockedTransaction->sale_id);
            if ($sale->status === 'completed') {
                $sameSettlement = $sale->payments->contains(fn (SalePayment $payment): bool => $payment->payment_method === 'mpesa'
                    && (int) data_get($payment->metadata, 'mpesa_transaction_id') === (int) $lockedTransaction->id
                );
                abort_unless($sameSettlement, 409, 'Another M-Pesa attempt already completed this sale.');

                return $sale;
            }
            abort_unless($sale->status === 'pending_payment', 409, 'Sale is no longer awaiting payment.');
            abort_unless((int) $sale->tenant_id === (int) $lockedTransaction->tenant_id
                && (int) $sale->shop_id === (int) $lockedTransaction->shop_id
                && bccomp((string) $sale->grand_total, (string) $lockedTransaction->amount, 2) === 0,
                422,
                'Verified M-Pesa amount or tenant scope does not match the pending sale.');

            $payload = $sale->pending_checkout_data;
            abort_unless(is_array($payload) && isset($payload['items'], $payload['payments']), 409, 'Pending checkout details are unavailable.');
            $previousTenant = TenantContext::getTenant();
            TenantContext::setTenant($sale->tenant);
            try {
                $user = User::query()->findOrFail($sale->user_id);
                // A callback may arrive after the timeout job released the reservation.
                // Reacquire atomically; if unavailable the paid attempt is flagged for refund.
                $this->inventory->reserveForPendingSale($sale, $payload['items'], $user);
                $this->inventory->settlePendingSaleReservation($sale, 'consumed');

                return $this->createSale(
                    shop: Shop::query()->findOrFail($sale->shop_id),
                    register: Register::query()->findOrFail($sale->register_id),
                    user: $user,
                    items: $payload['items'],
                    payments: $payload['payments'],
                    shift: $sale->cashierShift,
                    notes: $payload['notes'] ?? $sale->notes,
                    diningTableId: $sale->dining_table_id ? (int) $sale->dining_table_id : null,
                    idempotencyKey: (string) $sale->idempotency_key,
                    verifiedMpesaTransaction: $lockedTransaction,
                );
            } finally {
                TenantContext::setTenant($previousTenant);
            }
        });
    }

    /** @param array<int, array<string, mixed>> $items @return array{subtotal: string, discount_total: string, tax_total: string, grand_total: string} */
    private function previewMpesaTotal(Shop $shop, User $user, array $items): array
    {
        $subtotal = $discountTotal = $taxTotal = $grandTotal = '0.00';
        foreach ($items as $itemData) {
            $product = Product::query()->where('tenant_id', $shop->tenant_id)->where('is_active', true)->findOrFail($itemData['product_id']);
            $qty = number_format((float) ($itemData['quantity'] ?? 1), 4, '.', '');
            if ($shop->tenant?->industry === 'pharmacy') {
                $pharmacist = ! empty($itemData['pharmacist_user_id']) ? User::query()->findOrFail((int) $itemData['pharmacist_user_id']) : null;
                $this->pharmacy->validateItemForSale(
                    shop: $shop,
                    product: $product,
                    batchId: isset($itemData['batch_id']) ? (int) $itemData['batch_id'] : null,
                    authorizingPharmacist: $pharmacist,
                    quantity: (float) $qty,
                    prescriptionId: isset($itemData['prescription_id']) ? (int) $itemData['prescription_id'] : null,
                );
            }
            $discount = (string) ($itemData['discount'] ?? '0.00');
            $discountAllowed = $user->hasPermission('pos.discount.apply') || $user->hasPermission('pos.sale.discount') || $user->hasPermission('pos.discount.override');
            abort_if(bccomp($discount, '0.00', 2) > 0 && ! $discountAllowed, 403, 'Discount permission required.');
            $gross = bcmul((string) $product->selling_price, $qty, 2);
            abort_if(bccomp($discount, $gross, 2) > 0, 422, 'Discount cannot exceed item price.');
            if (bccomp($discount, '0.00', 2) > 0 && ! $user->hasPermission('pos.discount.override')) {
                $cap = bcdiv(bcmul($gross, number_format((float) config('pos.max_discount_percent', 10), 2, '.', ''), 4), '100.00', 2);
                abort_if(bccomp($discount, $cap, 2) > 0, 422, 'Discount exceeds the cashier discount limit.');
            }
            $lineSubtotal = bcsub(bcmul((string) $product->selling_price, $qty, 4), $discount, 2);
            $rate = (string) $product->tax_rate;
            if ($product->tax_type === 'inclusive' && (float) $rate > 0) {
                $divisor = bcadd('1', bcdiv($rate, '100', 4), 4);
                $lineTax = bcsub($lineSubtotal, bcdiv($lineSubtotal, $divisor, 2), 2);
                $lineTotal = $lineSubtotal;
            } elseif ($product->tax_type === 'exclusive' && (float) $rate > 0) {
                $lineTax = bcmul($lineSubtotal, bcdiv($rate, '100', 4), 2);
                $lineTotal = bcadd($lineSubtotal, $lineTax, 2);
            } else {
                $lineTax = '0.00';
                $lineTotal = $lineSubtotal;
            }
            $subtotal = bcadd($subtotal, $lineSubtotal, 2);
            $discountTotal = bcadd($discountTotal, $discount, 2);
            $taxTotal = bcadd($taxTotal, $lineTax, 2);
            $grandTotal = bcadd($grandTotal, $lineTotal, 2);
        }

        return ['subtotal' => $subtotal, 'discount_total' => $discountTotal, 'tax_total' => $taxTotal, 'grand_total' => $grandTotal];
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
