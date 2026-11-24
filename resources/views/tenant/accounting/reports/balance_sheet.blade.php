@extends('tenant.layout')

@section('title', 'Balance Sheet')

@section('content')
<header>
    <div>
        <h1>Balance Sheet</h1>
        <div class="muted">Financial position statement as of {{ date('F d, Y', strtotime($asOfDate)) }}</div>
    </div>
    <form method="GET" action="{{ route('tenant.accounting.reports.balance-sheet') }}" style="display: flex; gap: 8px; align-items: center;">
        <input type="date" name="as_of_date" value="{{ $asOfDate }}" style="width: auto;">
        <button type="submit" class="btn btn-secondary">Update Date</button>
    </form>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span class="muted" style="font-size: 13px; text-transform: uppercase;">Accounting Equation:</span>
            <span style="font-weight: 700; font-size: 14px; margin-left: 8px;">
                Assets = Liabilities + Equity
            </span>
            <span class="badge badge-{{ $report['is_balanced'] ? 'success' : 'danger' }}" style="font-size: 12px; margin-left: 10px;">
                {{ $report['is_balanced'] ? 'BALANCED' : 'OUT OF BALANCE' }}
            </span>
        </div>
        <div>
            <span class="muted" style="font-size: 13px;">Total Assets: <strong>KES {{ number_format((float) $report['total_assets'], 2) }}</strong></span>
            <span class="muted" style="margin-left: 16px; font-size: 13px;">Total Liab. & Equity: <strong>KES {{ number_format((float) $report['total_liabilities_and_equity'], 2) }}</strong></span>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    {{-- Left: Assets --}}
    <div class="panel">
        <h2 style="font-size: 17px; margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
            Assets
        </h2>
        <table style="margin-bottom: 20px;">
            <tbody>
                @forelse($report['asset_accounts'] as $acc)
                    <tr>
                        <td style="width: 100px;"><code>{{ $acc['code'] }}</code></td>
                        <td>{{ $acc['name'] }}</td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600;">
                            KES {{ number_format((float) $acc['amount'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No assets recorded.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #0f172a; font-size: 15px;">
                    <td colspan="2">Total Assets</td>
                    <td style="text-align: right; font-family: monospace; color: #15803d;">
                        KES {{ number_format((float) $report['total_assets'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Right: Liabilities & Equity --}}
    <div class="panel">
        <h2 style="font-size: 17px; margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
            Liabilities & Equity
        </h2>

        <h3 style="font-size: 14px; text-transform: uppercase; color: #64748b; margin-top: 14px;">1. Liabilities</h3>
        <table style="margin-bottom: 16px;">
            <tbody>
                @forelse($report['liability_accounts'] as $acc)
                    <tr>
                        <td style="width: 100px;"><code>{{ $acc['code'] }}</code></td>
                        <td>{{ $acc['name'] }}</td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600;">
                            KES {{ number_format((float) $acc['amount'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No liabilities recorded.</td></tr>
                @endforelse
                <tr style="background: #f8fafc; font-weight: 700;">
                    <td colspan="2">Total Liabilities</td>
                    <td style="text-align: right; font-family: monospace;">
                        KES {{ number_format((float) $report['total_liabilities'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <h3 style="font-size: 14px; text-transform: uppercase; color: #64748b; margin-top: 20px;">2. Equity</h3>
        <table style="margin-bottom: 16px;">
            <tbody>
                @forelse($report['equity_accounts'] as $acc)
                    <tr>
                        <td style="width: 100px;"><code>{{ $acc['code'] }}</code></td>
                        <td>{{ $acc['name'] }}</td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600;">
                            KES {{ number_format((float) $acc['amount'], 2) }}
                        </td>
                    </tr>
                @empty
                @endforelse
                <tr>
                    <td style="width: 100px;"><code>3999</code></td>
                    <td>Retained Earnings (Cumulative Net Income)</td>
                    <td style="text-align: right; font-family: monospace; font-weight: 600;">
                        KES {{ number_format((float) $report['retained_earnings'], 2) }}
                    </td>
                </tr>
                <tr style="background: #f8fafc; font-weight: 700;">
                    <td colspan="2">Total Equity</td>
                    <td style="text-align: right; font-family: monospace;">
                        KES {{ number_format((float) $report['total_equity'], 2) }}
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #0f172a; font-size: 15px;">
                    <td colspan="2">Total Liabilities & Equity</td>
                    <td style="text-align: right; font-family: monospace; color: #15803d;">
                        KES {{ number_format((float) $report['total_liabilities_and_equity'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
