@extends('platform.layout')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<header>
    <div>
        <h1>Invoice <code>{{ $invoice->invoice_number }}</code></h1>
        <div class="muted">Issued to {{ $invoice->tenant->name }} on {{ $invoice->created_at->format('Y-m-d') }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.invoices.index') }}" class="btn secondary">Back to Invoices</a>
        @if($invoice->amount_paid == '0.00' && !in_array($invoice->status, ['voided', 'cancelled'], true) && auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
            <form method="POST" action="{{ route('platform.invoices.void', $invoice) }}" data-motion-confirm="Void this unpaid invoice?" style="display: inline;">
                @csrf
                <input type="hidden" name="reason" value="Voided by billing administrator">
                <button type="submit" class="btn" style="background: #dc2626;">Void Invoice</button>
            </form>
        @endif
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

@if ($errors->any())
    <div class="notice" style="border-color: #DC2626; background: #EAEEF3; color: var(--clr-red);" role="alert">
        <strong>Please check the errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <h2>Invoice Line Items</h2>
            <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th style="text-align: left; padding: 8px;">Description</th>
                        <th style="text-align: center; padding: 8px;">Quantity</th>
                        <th style="text-align: right; padding: 8px;">Unit Price</th>
                        <th style="text-align: right; padding: 8px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 8px;">{{ $item->description }}</td>
                            <td style="text-align: center; padding: 10px 8px;">{{ $item->quantity }}</td>
                            <td style="text-align: right; padding: 10px 8px;">{{ $invoice->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td style="text-align: right; padding: 10px 8px; font-weight: 500;">{{ $invoice->currency }} {{ number_format((float) $item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 8px; color: #64748b;">Subtotal (excl. VAT):</td>
                        <td style="text-align: right; padding: 8px; font-weight: 600;">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 8px; color: #64748b;">Kenyan VAT ({{ $invoice->tax_rate }}%):</td>
                        <td style="text-align: right; padding: 8px; font-weight: 600;">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #e2e8f0; font-size: 16px;">
                        <td colspan="3" style="text-align: right; padding: 10px 8px; font-weight: bold;">Total Amount:</td>
                        <td style="text-align: right; padding: 10px 8px; font-weight: bold;">{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 8px; color: var(--clr-green);">Amount Paid:</td>
                        <td style="text-align: right; padding: 8px; font-weight: 600; color: var(--clr-green);">{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr style="font-size: 16px; border-top: 1px dashed #cbd5e1;">
                        <td colspan="3" style="text-align: right; padding: 10px 8px; font-weight: bold; color: #b45309;">Balance Due:</td>
                        <td style="text-align: right; padding: 10px 8px; font-weight: bold; color: #b45309;">{{ $invoice->currency }} {{ number_format((float) $invoice->amount_due, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="panel" style="margin-bottom: 20px;">
            <h2>Payment Ledger</h2>
            <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <th style="text-align: left; padding: 8px;">Reference</th>
                        <th style="text-align: left; padding: 8px;">Method</th>
                        <th style="text-align: right; padding: 8px;">Amount</th>
                        <th style="text-align: left; padding: 8px;">Paid At</th>
                        <th style="text-align: center; padding: 8px;">Status</th>
                        <th style="text-align: right; padding: 8px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->payments as $payment)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px;"><code>{{ $payment->payment_reference }}</code></td>
                            <td style="padding: 8px;">{{ strtoupper($payment->payment_method) }}</td>
                            <td style="text-align: right; padding: 8px; font-weight: 600;">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                            <td style="padding: 8px;">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td style="text-align: center; padding: 8px;"><span class="badge">{{ strtoupper($payment->status) }}</span></td>
                            <td style="text-align: right; padding: 8px;">
                                @if($payment->status === 'pending' && in_array($payment->payment_method, ['mpesa_paybill', 'bank_transfer', 'card'], true) && auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
                                    <form method="POST" action="{{ route('platform.invoices.payment.reconcile', [$invoice, $payment]) }}" style="display: inline-flex; gap: 6px; align-items: center;">
                                        @csrf
                                        <input name="evidence_reference" placeholder="Verified receipt / ref" required minlength="4" maxlength="128">
                                        <button type="submit" class="btn secondary" style="padding: 4px 8px; font-size: 12px;">Reconcile</button>
                                    </form>
                                @endif
                                @if(bccomp((string) $payment->refundableBalance(), '0.00', 2) > 0 && auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
                                    <form method="POST" action="{{ route('platform.invoices.refund', [$invoice, $payment]) }}" style="display: inline;" data-motion-confirm="Process refund of {{ $payment->currency }} {{ $payment->refundableBalance() }}?">
                                        @csrf
                                        <input type="hidden" name="amount" value="{{ $payment->refundableBalance() }}">
                                        <input type="hidden" name="reason" value="Customer refund request">
                                        <button type="submit" class="btn secondary" style="color: #dc2626; padding: 4px 8px; font-size: 12px;">Refund ({{ $payment->currency }} {{ $payment->refundableBalance() }})</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 16px;" class="muted">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoice->dunningEvents->isNotEmpty())
            <div class="panel">
                <h2>Dunning Reminders & Escalations</h2>
                <ul style="padding-left: 20px; margin-top: 10px;">
                    @foreach($invoice->dunningEvents as $event)
                        <li style="margin-bottom: 6px;">
                            <strong>{{ strtoupper($event->stage) }}</strong> via {{ $event->channel }} on {{ $event->triggered_at->format('Y-m-d H:i') }}
                            <span class="badge" style="font-size: 11px;">{{ $event->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div>
        <div class="panel" style="margin-bottom: 20px;">
            <h3>Invoice Details</h3>
            <p><strong>Tenant:</strong> {{ $invoice->tenant->name }}</p>
            <p><strong>Status:</strong> <span class="badge">{{ strtoupper($invoice->status) }}</span></p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date?->format('Y-m-d') }}</p>
            <p><strong>Period:</strong> {{ $invoice->period_start?->format('Y-m-d') }} to {{ $invoice->period_end?->format('Y-m-d') }}</p>
            @if($invoice->subscription)
                <p><strong>Plan:</strong> {{ $invoice->subscription->plan->name }} ({{ $invoice->subscription->billing_cycle }})</p>
            @endif
        </div>

        <div class="panel" style="margin-bottom: 20px; background: var(--clr-grey-tint); border-color: var(--clr-grey-tint);">
            <h3 style="color: var(--clr-muted);">eTIMS Fiscalisation</h3>
            <p style="font-size: 13px; color: #78350f;">
                Kenyan statutory compliance requirement (Annex A, 8.7).
            </p>
            <p><strong>Status:</strong> <span class="badge" style="background: var(--clr-grey-tint); color: var(--clr-muted);">BLOCKED-ON-CERTIFICATION</span></p>
            <p style="font-size: 12px; color: #78350f; margin-bottom: 0;">
                Platform invoice fiscalisation under BlePOS platform PIN is held until regulatory sandbox certification completes.
            </p>
        </div>

        @if($invoice->amount_due > 0 && !in_array($invoice->status, ['voided', 'cancelled'], true) && auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
            <div class="panel">
                <h3>Record Payment</h3>
                <form method="POST" action="{{ route('platform.invoices.payment', $invoice) }}" class="form" style="margin-top: 12px; display: flex; flex-direction: column; gap: 12px;">
                    @csrf
                    <x-input type="number" step="0.01" min="0.01" :max="$invoice->amount_due" name="amount" :label="'Amount (' . $invoice->currency . ')'" :value="$invoice->amount_due" required />

                    <x-input type="select" name="payment_method" label="Payment Method">
                        <option value="bank_transfer">Bank Transfer (EFT/RTGS)</option>
                        <option value="mpesa">M-Pesa Express / Paybill</option>
                        <option value="card">Card (Visa/Mastercard)</option>
                        <option value="manual">Manual / Offline Entry</option>
                    </x-input>

                    <x-input name="gateway_reference" label="Gateway / Transaction Ref" placeholder="e.g. QED9876543" />

                    <x-input type="textarea" name="notes" label="Notes" rows="2" />

                    <button type="submit" style="margin-top: 8px;">Record Payment</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
