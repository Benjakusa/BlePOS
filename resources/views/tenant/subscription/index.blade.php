@extends('tenant.layout')

@section('title', 'Subscription & Billing')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 22px; font-weight: 600; margin: 0; color: #0f172a;">Subscription &amp; Billing</h1>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Pay your subscription directly or settle open platform invoices anytime.</p>
    </div>
    <a href="{{ route('tenant.dashboard') }}" class="btn btn-secondary" style="text-decoration: none;">Back to Dashboard</a>
</div>

@if(session('success'))
    <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px;">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
    {{-- Current Plan Status --}}
    <div class="panel">
        <h2 style="font-size: 16px; margin-top: 0;">Current Subscription</h2>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13.5px;">
            <div><strong>Business:</strong> {{ $tenant->name }}</div>
            <div><strong>Current Plan:</strong> {{ $subscription?->plan?->name ?? 'Free Trial' }}</div>
            <div>
                <strong>Status:</strong>
                <span class="badge {{ ($subscription?->status ?? $tenant->status) === 'active' ? 'badge-success' : 'badge-warning' }}">
                    {{ strtoupper($subscription?->status ?? $tenant->status) }}
                </span>
            </div>
            <div><strong>Billing Cycle:</strong> {{ ucfirst($subscription?->billing_cycle ?? 'monthly') }}</div>
            @if($subscription?->renews_at)
                <div><strong>Renews On:</strong> {{ $subscription->renews_at->format('M d, Y') }}</div>
            @elseif($subscription?->trial_ends_at)
                <div><strong>Trial Ends:</strong> {{ $subscription->trial_ends_at->format('M d, Y') }}</div>
            @endif
        </div>
    </div>

    {{-- Pay Subscription Directly (No Invoice Required) --}}
    <div class="panel">
        <h2 style="font-size: 16px; margin-top: 0;">Pay Subscription Now</h2>
        <form action="{{ route('tenant.subscription.pay') }}" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
            @csrf

            <x-input type="select" name="plan_id" label="Select Plan" required>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('plan_id', $subscription?->plan_id) == $plan->id)>
                        {{ $plan->name }} — {{ $plan->currency }} {{ number_format((float) $plan->monthly_price, 0) }}/mo ({{ number_format((float) $plan->annual_price, 0) }}/yr)
                    </option>
                @endforeach
            </x-input>

            <x-input type="select" name="billing_cycle" label="Billing Cycle" required>
                <option value="monthly" @selected(old('billing_cycle', 'monthly') === 'monthly')>Monthly</option>
                <option value="annual" @selected(old('billing_cycle') === 'annual')>Annual</option>
            </x-input>

            <x-input type="select" id="sub-payment-method" name="payment_method" label="Payment Method" required>
                <option value="mpesa_stk" @selected(old('payment_method', 'mpesa_stk') === 'mpesa_stk')>M-Pesa STK Push</option>
                <option value="mpesa_paybill" @selected(old('payment_method') === 'mpesa_paybill')>M-Pesa Paybill Reference</option>
                <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
            </x-input>

            <div id="sub-phone-wrap">
                <x-input type="tel" name="phone_number" label="M-Pesa Phone Number (e.g. 0712345678)" value="{{ old('phone_number', $tenant->owner_phone) }}" />
            </div>

            <div id="sub-ref-wrap" style="display: none;">
                <x-input name="gateway_reference" label="Payment Reference / Transaction Code" value="{{ old('gateway_reference') }}" />
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-shield-check"></i> Pay &amp; Activate Subscription
            </button>
        </form>
    </div>
</div>

{{-- Open / Sent Platform Invoices --}}
<div class="panel" style="margin-bottom: 24px;">
    <h2 style="font-size: 16px; margin-top: 0;">Platform Invoices</h2>
    @if($invoices->isEmpty())
        <p class="muted" style="font-size: 13px;">No platform invoices have been issued yet. You can still pay your subscription directly above.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Due countdown</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                    <tr>
                        <td><strong>{{ $inv->invoice_number }}</strong></td>
                        <td><span class="badge {{ $inv->isPaid() ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($inv->status) }}</span></td>
                        <td>{{ $inv->currency }} {{ number_format((float) $inv->total_amount, 2) }}</td>
                        <td>{{ $inv->currency }} {{ number_format((float) $inv->amount_due, 2) }}</td>
                        <td>{{ $inv->due_date?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="badge {{ $inv->isOverdue() ? 'badge-danger' : ($inv->isPaid() ? 'badge-success' : 'badge-warning') }}">{{ $inv->dueCountdown() }}</span></td>
                        <td>
                            @if(! $inv->isPaid())
                                <form action="{{ route('tenant.subscription.pay') }}" method="POST" style="display: inline-flex; gap: 8px; align-items: center; margin: 0;">
                                    @csrf
                                    <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                                    <input type="hidden" name="payment_method" value="mpesa_stk">
                                    <div style="width: 170px;">
                                        <x-input type="tel" name="phone_number" label="M-Pesa Phone" value="{{ $tenant->owner_phone }}" :compact="true" required />
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Pay via STK Push</button>
                                </form>
                            @else
                                <span class="muted" style="font-size: 12px;">Paid {{ $inv->paid_at?->format('M d, Y') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Payment History --}}
<div class="panel">
    <h2 style="font-size: 16px; margin-top: 0;">Subscription Payment History</h2>
    @if($payments->isEmpty())
        <p class="muted" style="font-size: 13px;">No subscription payments recorded yet.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Invoice</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Gateway Ref</th>
                    <th>Paid At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $pay)
                    <tr>
                        <td>{{ $pay->payment_reference }}</td>
                        <td>{{ $pay->invoice?->invoice_number ?? '—' }}</td>
                        <td>{{ strtoupper($pay->payment_method) }}</td>
                        <td>{{ $pay->currency }} {{ number_format((float) $pay->amount, 2) }}</td>
                        <td><code>{{ $pay->gateway_reference ?? '—' }}</code></td>
                        <td>{{ $pay->paid_at?->format('M d, Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<script>
(function() {
    const method = document.getElementById('sub-payment-method');
    const phoneWrap = document.getElementById('sub-phone-wrap');
    const refWrap = document.getElementById('sub-ref-wrap');
    function syncFields() {
        const isStk = method.value === 'mpesa_stk';
        phoneWrap.style.display = isStk ? 'block' : 'none';
        refWrap.style.display = isStk ? 'none' : 'block';
    }
    method.addEventListener('change', syncFields);
    syncFields();
})();
</script>
@endsection
