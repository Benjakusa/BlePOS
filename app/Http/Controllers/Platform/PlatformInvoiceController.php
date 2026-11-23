<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Tenant;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformInvoiceController extends Controller
{
    public function __construct(
        protected PlatformBillingService $billing,
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.view'), 403);

        $query = PlatformInvoice::query()->with(['tenant', 'subscription'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }

        $invoices = $query->paginate(25)->withQueryString();
        $summary = $this->billing->getBillingSummary();

        return view('platform.billing.invoices.index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'selectedStatus' => $request->query('status', ''),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $tenants = Tenant::query()->orderBy('name')->get();

        return view('platform.billing.invoices.create', [
            'tenants' => $tenants,
            'selectedTenantId' => $request->query('tenant_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'due_date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $subscription = $tenant->subscriptions()->latest()->first();

        $invoice = $this->billing->generateInvoice(
            tenant: $tenant,
            subscription: $subscription,
            items: $validated['items'],
            dueDate: $validated['due_date'],
            currency: $validated['currency'] ?? 'KES',
            notes: $validated['notes'] ?? null,
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.invoice.generate',
            targetType: PlatformInvoice::class,
            targetId: (string) $invoice->id,
            details: [
                'invoice_number' => $invoice->invoice_number,
                'tenant_id' => $tenant->id,
                'total_amount' => $invoice->total_amount,
                'due_date' => $invoice->due_date->toDateString(),
            ],
        );

        return redirect()->route('platform.invoices.show', $invoice)
            ->with('notice', "Invoice {$invoice->invoice_number} generated successfully.");
    }

    public function show(Request $request, PlatformInvoice $invoice): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.view'), 403);

        $invoice->load([
            'tenant',
            'subscription.plan',
            'items',
            'payments.refunds',
            'dunningEvents' => fn ($q) => $q->latest(),
        ]);

        return view('platform.billing.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function recordPayment(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:mpesa,bank_transfer,card,manual'],
            'gateway_reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $this->billing->recordPayment(
            invoice: $invoice,
            amount: (string) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            gatewayReference: $validated['gateway_reference'] ?? null,
            notes: $validated['notes'] ?? null,
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.payment.record',
            targetType: PlatformPayment::class,
            targetId: (string) $payment->id,
            details: [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'payment_reference' => $payment->payment_reference,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
            ],
        );

        return redirect()->route('platform.invoices.show', $invoice)
            ->with('notice', "Payment of {$invoice->currency} {$payment->amount} recorded ({$payment->payment_reference}).");
    }

    public function recordRefund(Request $request, PlatformInvoice $invoice, PlatformPayment $payment): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $refund = $this->billing->recordRefund(
            payment: $payment,
            amount: (string) $validated['amount'],
            reason: $validated['reason'],
            actor: $request->user('platform'),
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.refund.record',
            targetType: PlatformPayment::class,
            targetId: (string) $payment->id,
            details: [
                'refund_reference' => $refund->refund_reference,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
            ],
        );

        return redirect()->route('platform.invoices.show', $invoice)
            ->with('notice', "Refund of {$invoice->currency} {$refund->amount} processed ({$refund->refund_reference}).");
    }

    public function void(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->billing->voidInvoice(
            invoice: $invoice,
            reason: $validated['reason'],
            actor: $request->user('platform'),
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.invoice.void',
            targetType: PlatformInvoice::class,
            targetId: (string) $invoice->id,
            details: [
                'invoice_number' => $invoice->invoice_number,
                'reason' => $validated['reason'],
            ],
        );

        return redirect()->route('platform.invoices.show', $invoice)
            ->with('notice', "Invoice {$invoice->invoice_number} voided.");
    }
}
