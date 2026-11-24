@extends('tenant.layout')

@section('title', 'Profit & Loss Statement')

@section('content')
<header>
    <div>
        <h1>Profit & Loss Statement</h1>
        <div class="muted">Income and expense performance from {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</div>
    </div>
    <form method="GET" action="{{ route('tenant.accounting.reports.profit-loss') }}" style="display: flex; gap: 8px; align-items: center;">
        <div style="width: 160px;">
            <x-input type="date" name="start_date" label="From Date" :value="$startDate" :compact="true" />
        </div>
        <span>to</span>
        <div style="width: 160px;">
            <x-input type="date" name="end_date" label="To Date" :value="$endDate" :compact="true" />
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</header>

<div class="grid-cards" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="label">Operating Revenue</div>
        <div class="value" style="color: #15803d;">KES {{ number_format((float) $report['total_revenue'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Cost of Goods Sold (COGS)</div>
        <div class="value" style="color: #64748b;">KES {{ number_format((float) $report['total_cogs'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Gross Profit</div>
        <div class="value" style="color: #0369a1;">KES {{ number_format((float) $report['gross_profit'], 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Net Operating Income</div>
        <div class="value" style="color: {{ bccomp($report['net_profit'], '0.00', 2) >= 0 ? '#15803d' : '#dc2626' }};">
            KES {{ number_format((float) $report['net_profit'], 2) }}
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom: 24px;">
    <h2 style="font-size: 16px; margin-top: 0; margin-bottom: 12px; color: #15803d;">1. Operating Revenue</h2>
    <table style="margin-bottom: 20px;">
        <tbody>
            @forelse($report['revenue_accounts'] as $item)
                <tr>
                    <td style="width: 120px;"><code>{{ $item['code'] }}</code></td>
                    <td>{{ $item['name'] }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600;">KES {{ number_format((float) $item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No revenue recorded in this period.</td></tr>
            @endforelse
            <tr style="font-weight: 750; background: #f8fafc; border-top: 1px solid #cbd5e1;">
                <td colspan="2">Total Operating Revenue</td>
                <td style="text-align: right; font-family: monospace;">KES {{ number_format((float) $report['total_revenue'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="font-size: 16px; margin-top: 24px; margin-bottom: 12px; color: #475569;">2. Cost of Goods Sold</h2>
    <table style="margin-bottom: 20px;">
        <tbody>
            @forelse($report['cogs_accounts'] as $item)
                <tr>
                    <td style="width: 120px;"><code>{{ $item['code'] }}</code></td>
                    <td>{{ $item['name'] }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600;">KES {{ number_format((float) $item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No COGS recorded in this period.</td></tr>
            @endforelse
            <tr style="font-weight: 750; background: #f8fafc; border-top: 1px solid #cbd5e1;">
                <td colspan="2">Total Cost of Goods Sold</td>
                <td style="text-align: right; font-family: monospace;">KES {{ number_format((float) $report['total_cogs'], 2) }}</td>
            </tr>
            <tr style="font-weight: 800; background: #EAEEF3; border-top: 2px solid #86efac; color: #166534;">
                <td colspan="2">Gross Profit (Revenue - COGS)</td>
                <td style="text-align: right; font-family: monospace;">KES {{ number_format((float) $report['gross_profit'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="font-size: 16px; margin-top: 24px; margin-bottom: 12px; color: #991b1b;">3. Operating Expenses</h2>
    <table style="margin-bottom: 20px;">
        <tbody>
            @forelse($report['expense_accounts'] as $item)
                <tr>
                    <td style="width: 120px;"><code>{{ $item['code'] }}</code></td>
                    <td>{{ $item['name'] }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600;">KES {{ number_format((float) $item['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No operating expenses recorded in this period.</td></tr>
            @endforelse
            <tr style="font-weight: 750; background: #f8fafc; border-top: 1px solid #cbd5e1;">
                <td colspan="2">Total Operating Expenses</td>
                <td style="text-align: right; font-family: monospace;">KES {{ number_format((float) $report['total_expenses'], 2) }}</td>
            </tr>
            <tr style="font-weight: 800; background: #f8fafc; border-top: 2px solid #0f172a; font-size: 15px;">
                <td colspan="2">Net Profit / (Loss)</td>
                <td style="text-align: right; font-family: monospace; color: {{ bccomp($report['net_profit'], '0.00', 2) >= 0 ? '#15803d' : '#dc2626' }};">
                    KES {{ number_format((float) $report['net_profit'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
