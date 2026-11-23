@extends('platform.layout')

@section('title', 'Platform Invoices')

@section('content')
<header>
    <div>
        <h1>Platform Invoices & Billing</h1>
        <div class="muted">Platform subscription billing, invoice lifecycle, tax calculation, and collections</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.dunning.index') }}" class="btn secondary">Dunning & Collections</a>
        @if(auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
            <a href="{{ route('platform.invoices.create') }}" class="btn">Issue Invoice</a>
        @endif
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Total Invoiced</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">KES {{ number_format((float) $summary['total_invoiced'], 2) }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Total Collected</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #146b4d;">KES {{ number_format((float) $summary['total_collected'], 2) }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Total Outstanding</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #b45309;">KES {{ number_format((float) $summary['total_outstanding'], 2) }}</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Collection Rate</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px;">{{ $summary['collection_rate'] }}%</div>
    </div>
    <div class="panel" style="padding: 16px;">
        <div class="muted" style="font-size: 13px;">Overdue Invoices</div>
        <div style="font-size: 22px; font-weight: bold; margin-top: 4px; color: #dc2626;">{{ $summary['overdue_count'] }}</div>
    </div>
</div>

<div class="panel" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left;">Invoice #</th>
                <th style="text-align: left;">Tenant</th>
                <th style="text-align: left;">Due Date</th>
                <th style="text-align: right;">Total</th>
                <th style="text-align: right;">Paid</th>
                <th style="text-align: right;">Balance</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">eTIMS</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><code>{{ $invoice->invoice_number }}</code></td>
                    <td>
                        <a href="{{ route('platform.tenants.show', $invoice->tenant) }}"><strong>{{ $invoice->tenant->name }}</strong></a>
                    </td>
                    <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                    <td style="text-align: right; color: #146b4d;">{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                    <td style="text-align: right; font-weight: 600;">{{ $invoice->currency }} {{ number_format((float) $invoice->amount_due, 2) }}</td>
                    <td style="text-align: center;">
                        <span class="badge">{{ strtoupper($invoice->status) }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge" style="font-size: 11px;">{{ $invoice->etims_status }}</span>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('platform.invoices.show', $invoice) }}">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 24px;" class="muted">No invoices found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
