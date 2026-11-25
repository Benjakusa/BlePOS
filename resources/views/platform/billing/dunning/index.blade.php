@extends('platform.layout')

@section('title', 'Dunning & Collections')

@section('content')
<header>
    <div>
        <h1>Dunning & Collections Engine</h1>
        <div class="muted">Automated past-due escalations, overdue warnings, grace period tracking, and suspension lifecycle</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('platform.invoices.index') }}" class="btn secondary">Back to Invoices</a>
        @if(auth('platform')->user()->hasPermission('platform.billing.subscriptions.manage'))
            <form method="POST" action="{{ route('platform.dunning.trigger') }}" data-motion-confirm="Run automated dunning evaluation across all overdue invoices?" style="display: inline;">
                @csrf
                <button type="submit" class="btn">Run Dunning Cycle</button>
            </form>
        @endif
    </div>
</header>

@if(session('notice'))
    <div class="notice" role="alert">{{ session('notice') }}</div>
@endif

<div class="panel" style="margin-bottom: 24px;">
    <h2>Overdue Invoices Awaiting Collection ({{ $overdueInvoices->count() }})</h2>
    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <thead>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="text-align: left; padding: 8px;">Invoice #</th>
                <th style="text-align: left; padding: 8px;">Tenant</th>
                <th style="text-align: left; padding: 8px;">Due Date</th>
                <th style="text-align: center; padding: 8px;">Days Overdue</th>
                <th style="text-align: right; padding: 8px;">Amount Due</th>
                <th style="text-align: center; padding: 8px;">Subscription Status</th>
                <th style="text-align: right; padding: 8px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($overdueInvoices as $inv)
                @php
                    $days = (int) now()->startOfDay()->diffInDays($inv->due_date);
                @endphp
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px;"><code>{{ $inv->invoice_number }}</code></td>
                    <td style="padding: 8px;">{{ $inv->tenant->name }}</td>
                    <td style="padding: 8px;">{{ $inv->due_date?->format('Y-m-d') }}</td>
                    <td style="text-align: center; padding: 8px; font-weight: bold; color: {{ $days >= 14 ? '#dc2626' : '#b45309' }};">
                        {{ $days }} days
                    </td>
                    <td style="text-align: right; padding: 8px; font-weight: bold;">{{ $inv->currency }} {{ number_format((float) $inv->amount_due, 2) }}</td>
                    <td style="text-align: center; padding: 8px;">
                        <span class="badge">{{ $inv->subscription ? strtoupper($inv->subscription->status) : 'N/A' }}</span>
                    </td>
                    <td style="text-align: right; padding: 8px;">
                        <a href="{{ route('platform.invoices.show', $inv) }}">Manage</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 16px;" class="muted">No overdue invoices. All accounts are in good standing!</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel">
    <h2>Recent Dunning Events & Escalations</h2>
    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <thead>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="text-align: left; padding: 8px;">Timestamp</th>
                <th style="text-align: left; padding: 8px;">Tenant</th>
                <th style="text-align: left; padding: 8px;">Invoice</th>
                <th style="text-align: center; padding: 8px;">Stage</th>
                <th style="text-align: center; padding: 8px;">Channel</th>
                <th style="text-align: center; padding: 8px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px;">{{ $event->triggered_at->format('Y-m-d H:i') }}</td>
                    <td style="padding: 8px;">{{ $event->tenant->name }}</td>
                    <td style="padding: 8px;"><code>{{ $event->invoice->invoice_number }}</code></td>
                    <td style="text-align: center; padding: 8px;"><span class="badge">{{ strtoupper($event->stage) }}</span></td>
                    <td style="text-align: center; padding: 8px;">{{ strtoupper($event->channel) }}</td>
                    <td style="text-align: center; padding: 8px;"><span class="badge" style="font-size: 11px;">{{ strtoupper($event->status) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 16px;" class="muted">No dunning events recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $events->links() }}
    </div>
</div>
@endsection
