@extends('tenant.layout')

@section('title', 'Journal Entry ' . $entry->entry_number)

@section('content')
<header>
    <div>
        <h1>Journal Entry #{{ $entry->entry_number }}</h1>
        <div class="muted">Posted on {{ $entry->entry_date->format('F d, Y') }} · Created by {{ $entry->createdByUser->name ?? 'System' }}</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.journals.index') }}" class="btn btn-secondary">← Back to Entries</a>
    </div>
</header>

<div class="panel" style="margin-bottom: 24px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
        <div>
            <span class="muted" style="font-size: 12px; text-transform: uppercase;">Status</span>
            <div style="margin-top: 4px;">
                <span class="badge badge-{{ $entry->status === 'posted' ? 'success' : 'danger' }}">
                    {{ strtoupper($entry->status) }}
                </span>
            </div>
        </div>
        <div>
            <span class="muted" style="font-size: 12px; text-transform: uppercase;">Source Reference</span>
            <div style="font-weight: 600; margin-top: 4px;">{{ $entry->reference_type ?? 'Manual' }} {{ $entry->reference_id ? '#'.$entry->reference_id : '' }}</div>
        </div>
        <div>
            <span class="muted" style="font-size: 12px; text-transform: uppercase;">Branch</span>
            <div style="font-weight: 600; margin-top: 4px;">{{ $entry->shop->name ?? 'All Branches' }}</div>
        </div>
        <div>
            <span class="muted" style="font-size: 12px; text-transform: uppercase;">Description</span>
            <div style="font-weight: 600; margin-top: 4px;">{{ $entry->description }}</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Memo / Description</th>
                <th style="text-align: right;">Debit (KES)</th>
                <th style="text-align: right;">Credit (KES)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->lines as $line)
                <tr>
                    <td><code>{{ $line->account->code }}</code></td>
                    <td><strong>{{ $line->account->name }}</strong></td>
                    <td class="muted">{{ ucfirst($line->account->type) }}</td>
                    <td>{{ $line->memo ?? '—' }}</td>
                    <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-weight: 600;">
                        {{ bccomp((string) $line->debit, '0.00', 2) > 0 ? number_format((float) $line->debit, 2) : '—' }}
                    </td>
                    <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-weight: 600;">
                        {{ bccomp((string) $line->credit, '0.00', 2) > 0 ? number_format((float) $line->credit, 2) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: var(--clr-grey-tint); font-weight: 750; border-top: 2px solid #cbd5e1;">
                <td colspan="4" style="text-align: right; padding: 12px 14px;">Total Balanced:</td>
                <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-size: 15px;">{{ number_format((float) $entry->total_debit, 2) }}</td>
                <td style="text-align: right; font-family: Helvetica, Arial, sans-serif; font-size: 15px;">{{ number_format((float) $entry->total_credit, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
