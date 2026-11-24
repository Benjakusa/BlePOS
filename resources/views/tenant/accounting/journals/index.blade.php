@extends('tenant.layout')

@section('title', 'Journal Entries')

@section('content')
<header>
    <div>
        <h1>Journal Entries</h1>
        <div class="muted">Double-entry audit log of all financial transactions</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.journals.create') }}" class="btn">+ New Manual Entry</a>
    </div>
</header>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Entry #</th>
                <th>Date</th>
                <th>Description</th>
                <th>Source / Ref</th>
                <th>Branch</th>
                <th style="text-align: right;">Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td><code>{{ $entry->entry_number }}</code></td>
                    <td>{{ $entry->entry_date->format('M d, Y') }}</td>
                    <td><strong>{{ $entry->description }}</strong></td>
                    <td>
                        <span class="badge badge-secondary">
                            {{ $entry->reference_type ?? 'Manual' }} {{ $entry->reference_id ? '#'.$entry->reference_id : '' }}
                        </span>
                    </td>
                    <td class="muted">{{ $entry->shop->name ?? 'All Branches' }}</td>
                    <td style="text-align: right; font-weight: 650; font-family: monospace;">
                        {{ number_format((float) $entry->total_debit, 2) }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $entry->status === 'posted' ? 'success' : 'danger' }}">
                            {{ strtoupper($entry->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('tenant.accounting.journals.show', $entry) }}" class="btn btn-secondary btn-sm">Inspect</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align: center; padding: 24px;">No journal entries posted yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $entries->links() }}
</div>
@endsection
