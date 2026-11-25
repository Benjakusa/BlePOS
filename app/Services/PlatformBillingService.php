<?php

namespace App\Services;

use App\Models\MpesaTransaction;
use App\Models\PlatformDunningEvent;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\PlatformRefund;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformBillingService
{
    /** Create an untrusted payment attempt without applying it to an invoice. */
    public function createPaymentAttempt(
        PlatformInvoice $invoice,
        string $amount,
        string $paymentMethod,
        ?string $gatewayReference = null,
        ?string $notes = null
    ): PlatformPayment {
        $amount = number_format((float) $amount, 2, '.', '');
        if (bccomp($amount, '0.00', 2) <= 0 || bccomp($amount, (string) $invoice->amount_due, 2) > 0) {
            throw ValidationException::withMessages(['amount' => 'Payment attempt must be positive and no greater than the outstanding balance.']);
        }

        return PlatformPayment::query()->create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'payment_reference' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'currency' => $invoice->currency,
            'amount' => $amount,
            'gateway_reference' => $gatewayReference,
            'paid_at' => null,
            'notes' => $notes,
        ]);
    }

    /**
     * Generate an invoice for a tenant with line items and VAT calculation.
     *
     * @param  array<int, array{description: string, quantity: float|int|string, unit_price: float|int|string}>  $items
     */
    public function generateInvoice(
        Tenant $tenant,
        ?Subscription $subscription,
        array $items,
        Carbon|string $dueDate,
        string $currency = 'KES',
        ?string $notes = null
    ): PlatformInvoice {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'An invoice must have at least one line item.',
            ]);
        }

        $dueDate = is_string($dueDate) ? Carbon::parse($dueDate) : $dueDate;

        return DB::transaction(function () use ($tenant, $subscription, $items, $dueDate, $currency, $notes): PlatformInvoice {
            $subtotal = '0.00';
            $processedItems = [];

            foreach ($items as $item) {
                $qty = number_format((float) $item['quantity'], 2, '.', '');
                $unitPrice = number_format((float) $item['unit_price'], 2, '.', '');
                $lineTotal = bcmul($qty, $unitPrice, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $processedItems[] = [
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $lineTotal,
                ];
            }

            // 16% standard VAT
            $taxRate = '16.00';
            $taxAmount = bcdiv(bcmul($subtotal, $taxRate, 4), '100.00', 2);
            $totalAmount = bcadd($subtotal, $taxAmount, 2);

            $invoiceNumber = 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            $invoice = PlatformInvoice::query()->create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'issued',
                'currency' => $currency,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => '0.00',
                'amount_due' => $totalAmount,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'issued_at' => now(),
                'etims_status' => 'blocked_on_certification',
                'notes' => $notes,
            ]);

            foreach ($processedItems as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(
        PlatformInvoice $invoice,
        string $amount,
        string $paymentMethod,
        string $source,
        string $evidenceReference,
        PlatformUser $reconciler,
        ?string $notes = null
    ): PlatformPayment {
        if ($source !== 'platform_reconciliation' || ! $reconciler->hasPermission('platform.billing.subscriptions.manage') || trim($evidenceReference) === '') {
            throw new \DomainException('A privileged platform reconciliation with evidence is required to confirm invoice payment.');
        }

        $amount = number_format((float) $amount, 2, '.', '');

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        if (bccomp($amount, (string) $invoice->amount_due, 2) > 0) {
            throw ValidationException::withMessages([
                'amount' => "Payment amount ({$amount}) exceeds outstanding invoice due balance ({$invoice->amount_due}).",
            ]);
        }

        $payment = PlatformPayment::query()->create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'payment_reference' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'currency' => $invoice->currency,
            'amount' => $amount,
            'gateway_reference' => $evidenceReference,
            'paid_at' => null,
            'notes' => trim(($notes ?? '').' [source=platform_reconciliation; reconciler='.$reconciler->id.']'),
        ]);

        return $this->confirmPayment($payment);
    }

    /** Confirm a previously stored payment attempt after an authenticated provider callback. */
    public function confirmVerifiedProviderPayment(
        PlatformPayment $payment,
        MpesaTransaction $transaction,
        string $providerReceipt
    ): PlatformPayment {
        if ($transaction->status !== 'confirmed'
            || (int) $transaction->platform_payment_id !== (int) $payment->id
            || ! hash_equals((string) $transaction->provider_receipt, $providerReceipt)) {
            throw new \DomainException('A persisted, verified provider transaction is required to confirm settlement.');
        }

        $payment->forceFill([
            'gateway_reference' => $providerReceipt,
            'notes' => trim(($payment->notes ?? '').' [source=verified_provider]'),
        ])->save();

        return $this->confirmPayment($payment);
    }

    private function confirmPayment(PlatformPayment $payment): PlatformPayment
    {
        return DB::transaction(function () use ($payment): PlatformPayment {
            $lockedPayment = PlatformPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status !== 'pending') {
                if (in_array($lockedPayment->status, ['completed', 'partially_refunded', 'refunded'], true)) {
                    return $lockedPayment;
                }
                throw new \DomainException('Only a pending payment attempt can be confirmed.');
            }

            $invoice = PlatformInvoice::query()->lockForUpdate()->findOrFail($lockedPayment->platform_invoice_id);
            $amount = (string) $lockedPayment->amount;
            if (bccomp($amount, '0.00', 2) <= 0 || bccomp($amount, (string) $invoice->amount_due, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Confirmed payment exceeds the remaining invoice balance.']);
            }

            $lockedPayment->forceFill(['status' => 'completed', 'paid_at' => now()])->save();
            $newAmountPaid = bcadd((string) $invoice->amount_paid, $amount, 2);
            $newAmountDue = bcsub((string) $invoice->total_amount, $newAmountPaid, 2);
            $isPaid = bccomp($newAmountDue, '0.00', 2) <= 0;

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'amount_due' => $newAmountDue,
                'status' => $isPaid ? 'paid' : 'partially_paid',
                'paid_at' => $isPaid ? now() : $invoice->paid_at,
            ]);

            if ($isPaid && $invoice->subscription_id) {
                $sub = $invoice->subscription;
                if ($sub && in_array($sub->status, ['awaiting_payment', 'past_due', 'suspended'], true)) {
                    $sub->update(['status' => 'active']);
                    if ($invoice->tenant->status === 'suspended') {
                        $invoice->tenant->update([
                            'status' => 'active',
                            'suspended_at' => null,
                            'suspension_reason' => null,
                        ]);
                    }
                }
            }

            return $lockedPayment;
        });
    }

    /**
     * Record a refund against a payment.
     */
    public function recordRefund(
        PlatformPayment $payment,
        string $amount,
        string $reason,
        ?PlatformUser $actor = null
    ): PlatformRefund {
        $amount = number_format((float) $amount, 2, '.', '');

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Refund amount must be greater than zero.',
            ]);
        }

        $refundableBalance = $payment->refundableBalance();
        if (bccomp($amount, $refundableBalance, 2) > 0) {
            throw ValidationException::withMessages([
                'amount' => "Refund amount ({$amount}) exceeds available payment balance ({$refundableBalance}).",
            ]);
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $actor): PlatformRefund {
            $invoice = $payment->invoice;

            $refund = PlatformRefund::query()->create([
                'platform_payment_id' => $payment->id,
                'platform_invoice_id' => $invoice->id,
                'tenant_id' => $payment->tenant_id,
                'platform_user_id' => $actor?->id,
                'refund_reference' => 'REF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            // Update payment status
            $remaining = bcsub($payment->refundableBalance(), $amount, 2);
            $payment->update([
                'status' => bccomp($remaining, '0.00', 2) <= 0 ? 'refunded' : 'partially_refunded',
            ]);

            // Update invoice balances
            $newAmountPaid = bcsub((string) $invoice->amount_paid, $amount, 2);
            $newAmountDue = bcsub((string) $invoice->total_amount, $newAmountPaid, 2);

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'amount_due' => $newAmountDue,
                'status' => bccomp($newAmountPaid, '0.00', 2) <= 0 ? 'issued' : 'partially_paid',
                'paid_at' => null,
            ]);

            return $refund;
        });
    }

    /**
     * Void an unpaid invoice.
     */
    public function voidInvoice(PlatformInvoice $invoice, string $reason, ?PlatformUser $actor = null): PlatformInvoice
    {
        if (bccomp((string) $invoice->amount_paid, '0.00', 2) > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'Cannot void an invoice that has recorded payments. Process refunds first.',
            ]);
        }

        if (in_array($invoice->status, ['voided', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'invoice' => "Invoice is already {$invoice->status}.",
            ]);
        }

        $invoice->update([
            'status' => 'voided',
            'notes' => trim(($invoice->notes ?? '')."\nVoided on ".now()->toIso8601String().": {$reason}"),
        ]);

        return $invoice;
    }

    /**
     * Run the automated dunning cycle across overdue invoices.
     *
     * @return array{overdue_evaluated: int, events_created: int, subscriptions_past_due: int, tenants_suspended: int}
     */
    public function processDunning(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $today = $asOf->copy()->startOfDay();

        $overdueInvoices = PlatformInvoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('due_date', '<', $today->toDateString())
            ->where('amount_due', '>', 0)
            ->with(['tenant', 'subscription'])
            ->get();

        $eventsCreated = 0;
        $subscriptionsPastDue = 0;
        $tenantsSuspended = 0;

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = (int) Carbon::parse($invoice->due_date)->diffInDays($today);

            $stage = match (true) {
                $daysOverdue >= 14 => 'suspended',
                $daysOverdue >= 7 => 'overdue_7d',
                $daysOverdue >= 3 => 'overdue_3d',
                default => 'overdue_1d',
            };

            // Check if this dunning event stage was already triggered for this invoice
            $alreadyTriggered = PlatformDunningEvent::query()
                ->where('platform_invoice_id', $invoice->id)
                ->where('stage', $stage)
                ->exists();

            if (! $alreadyTriggered) {
                PlatformDunningEvent::query()->create([
                    'tenant_id' => $invoice->tenant_id,
                    'platform_invoice_id' => $invoice->id,
                    'stage' => $stage,
                    'channel' => 'email',
                    'status' => 'sent',
                    'triggered_at' => now(),
                    'details' => [
                        'days_overdue' => $daysOverdue,
                        'amount_due' => $invoice->amount_due,
                        'invoice_number' => $invoice->invoice_number,
                    ],
                ]);
                $eventsCreated++;
            }

            // Update subscription state
            $sub = $invoice->subscription;
            if ($stage === 'suspended') {
                if ($sub && $sub->status !== 'suspended') {
                    $sub->update(['status' => 'suspended']);
                    $subscriptionsPastDue++;
                }

                // Suspend tenant if not already suspended (guarantee: never suspended twice by duplicate events)
                if ($invoice->tenant->status !== 'suspended') {
                    $invoice->tenant->update([
                        'status' => 'suspended',
                        'suspended_at' => now(),
                        'suspension_reason' => "Billing dunning: invoice {$invoice->invoice_number} is {$daysOverdue} days overdue.",
                    ]);
                    $tenantsSuspended++;
                }
            } elseif ($stage !== 'suspended' && $sub && $sub->status === 'active') {
                $sub->update(['status' => 'past_due']);
                $subscriptionsPastDue++;
            }
        }

        return [
            'overdue_evaluated' => $overdueInvoices->count(),
            'events_created' => $eventsCreated,
            'subscriptions_past_due' => $subscriptionsPastDue,
            'tenants_suspended' => $tenantsSuspended,
        ];
    }

    /**
     * Get aggregate platform billing and collections metrics.
     *
     * @return array<string, mixed>
     */
    public function getBillingSummary(): array
    {
        $invoices = PlatformInvoice::query()->whereNotIn('status', ['voided', 'cancelled'])->get();

        $totalInvoiced = '0.00';
        $totalCollected = '0.00';
        $totalOutstanding = '0.00';

        foreach ($invoices as $inv) {
            $totalInvoiced = bcadd($totalInvoiced, (string) $inv->total_amount, 2);
            $totalCollected = bcadd($totalCollected, (string) $inv->amount_paid, 2);
            if (! $inv->isPaid()) {
                $totalOutstanding = bcadd($totalOutstanding, (string) $inv->amount_due, 2);
            }
        }

        $collectionRate = bccomp($totalInvoiced, '0.00', 2) > 0
            ? bcmul(bcdiv($totalCollected, $totalInvoiced, 4), '100.00', 2)
            : '0.00';

        $overdueCount = PlatformInvoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->count();

        return [
            'total_invoiced' => $totalInvoiced,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $collectionRate,
            'overdue_count' => $overdueCount,
        ];
    }
}
