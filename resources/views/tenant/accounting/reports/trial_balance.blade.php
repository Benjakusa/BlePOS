@extends('tenant.layout')

@section('title', 'Trial Balance')

@section('content')
<header>
    <div>
        <h1>Trial Balance</h1>
        <div class="muted">Verification of double-entry equality as of {{ date('F d, Y', strtotime($asOfDate)) }}</div>
    </div>
    <form method="GET" action="{{ route('tenant.accounting.reports.trial-balance') }}" style="display: flex; gap: 8px; align-items: center;">
        <div style="width: 170px;">
            <x-input type="date" name="as_of_date" label="As of Date" :value="$asOfDate" :compact="true" />
        </div>
        <button type="submit" class="btn btn-secondary">Update Date</button>
    </form>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span class="muted" style="font-size: 13px; text-transform: uppercase;">Ledger Integrity Status:</span>
            <span class="badge badge-{{ $report['is_balanced'] ? 'success' : 'danger' }}" style="font-size: 13px; margin-left: 8px;">
                {{ $report['is_balanced'] ? 'BALANCED' : 'OUT OF BALANCE' }}
            </span>
        </div>
        <div>
            <span class="muted" style="font-size: 13px;">Total Debits: <strong>{{ number_format((float) $report['total_debit'], 2) }}</strong></span>
            <span class="muted" style="margin-left: 16px; font-size: 13px;">Total Credits: <strong>{{ number_format((float) $report['total_credit'], 2) }}</strong></span>
        </div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Classification</th>
                <th style="text-align: right;">Debit (KES)</th>
                <th style="text-align: right;">Credit (KES)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['accounts'] as $row)
                <tr>
                    <td><code>{{ $row['code'] }}</code></td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td class="muted">{{ ucfirst($row['type']) }}</td>
                    <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-weight: 600;">
                        {{ bccomp((string) $row['debit'], '0.00', 2) > 0 ? number_format((float) $row['debit'], 2) : '—' }}
                    </td>
                    <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-weight: 600;">
                        {{ bccomp((string) $row['credit'], '0.00', 2) > 0 ? number_format((float) $row['credit'], 2) : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted" style="text-align: center; padding: 24px;">No active transactions recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: var(--clr-grey-tint); font-weight: 750; border-top: 2px solid #cbd5e1; font-size: 15px;">
                <td colspan="3" style="text-align: right; padding: 12px 14px;">Total Balanced:</td>
                <td style="text-align: right; font-family: Helvetica, Arial, sans-serif;">{{ number_format((float) $report['total_debit'], 2) }}</td>
                <td style="text-align: right; font-family: Helvetica, Arial, sans-serif;">{{ number_format((float) $report['total_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
