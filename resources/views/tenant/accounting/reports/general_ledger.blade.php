@extends('tenant.layout')

@section('title', 'General Ledger')

@section('content')
<header>
    <div>
        <h1>General Ledger</h1>
        <div class="muted">Detailed transactional ledger statement by account</div>
    </div>
    <form method="GET" action="{{ route('tenant.accounting.reports.general-ledger') }}" style="display: flex; gap: 8px; align-items: center;">
        <div style="min-width: 260px;">
            <x-input type="select" name="account_id" label="Account" onchange="this.form.submit()" :compact="true">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ ($selectedAccount->id ?? 0) == $acc->id ? 'selected' : '' }}>
                        [{{ $acc->code }}] {{ $acc->name }} ({{ ucfirst($acc->type) }})
                    </option>
                @endforeach
            </x-input>
        </div>
        <button type="submit" class="btn btn-secondary">Select</button>
    </form>
</header>

@if($selectedAccount)
    <div class="panel" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="muted" style="font-size: 13px; text-transform: uppercase;">Account:</span>
                <span style="font-weight: 750; font-size: 18px; margin-left: 8px;">
                    [{{ $selectedAccount->code }}] {{ $selectedAccount->name }}
                </span>
                <span class="badge badge-secondary" style="margin-left: 8px;">{{ ucfirst($selectedAccount->type) }}</span>
            </div>
            <div>
                <span class="muted" style="font-size: 13px; text-transform: uppercase;">Current Balance:</span>
                <span style="font-weight: 750; font-size: 18px; font-family: monospace; margin-left: 8px; color: #15803d;">
                    KES {{ number_format((float) $selectedAccount->current_balance, 2) }}
                </span>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Journal #</th>
                    <th>Source Ref</th>
                    <th>Memo / Description</th>
                    <th style="text-align: right;">Debit (KES)</th>
                    <th style="text-align: right;">Credit (KES)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $line)
                    <tr>
                        <td>{{ $line->journalEntry->entry_date->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('tenant.accounting.journals.show', $line->journalEntry) }}">
                                <code>{{ $line->journalEntry->entry_number }}</code>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-secondary">
                                {{ $line->journalEntry->reference_type ?? 'Manual' }}
                            </span>
                        </td>
                        <td>{{ $line->memo ?? $line->journalEntry->description }}</td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600;">
                            {{ bccomp((string) $line->debit, '0.00', 2) > 0 ? number_format((float) $line->debit, 2) : '—' }}
                        </td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600;">
                            {{ bccomp((string) $line->credit, '0.00', 2) > 0 ? number_format((float) $line->credit, 2) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 24px;">No transactions recorded for this account.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
