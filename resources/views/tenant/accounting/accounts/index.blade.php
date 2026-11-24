@extends('tenant.layout')

@section('title', 'Chart of Accounts')

@section('content')
<header>
    <div>
        <h1>Chart of Accounts</h1>
        <div class="muted">Standard double-entry accounts ledger for financial accounting and reporting</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.accounts.create') }}" class="btn">+ New Account</a>
    </div>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('tenant.accounting.accounts.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search by code or account name..." style="max-width: 280px;">
        <select name="type" style="max-width: 180px;">
            <option value="">All Account Types</option>
            <option value="asset" {{ ($type ?? '') === 'asset' ? 'selected' : '' }}>Assets</option>
            <option value="liability" {{ ($type ?? '') === 'liability' ? 'selected' : '' }}>Liabilities</option>
            <option value="equity" {{ ($type ?? '') === 'equity' ? 'selected' : '' }}>Equity</option>
            <option value="revenue" {{ ($type ?? '') === 'revenue' ? 'selected' : '' }}>Revenue</option>
            <option value="expense" {{ ($type ?? '') === 'expense' ? 'selected' : '' }}>Expenses</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(!empty($q) || !empty($type))
            <a href="{{ route('tenant.accounting.accounts.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Sub-Type</th>
                <th>Currency</th>
                <th style="text-align: right;">Current Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td><strong>{{ $account->code }}</strong></td>
                    <td>
                        {{ $account->name }}
                        @if($account->is_system)
                            <span class="badge badge-secondary" style="margin-left: 6px;">System</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ match($account->type) { 'asset' => 'info', 'liability' => 'warning', 'equity' => 'success', 'revenue' => 'success', default => 'secondary' } }}">
                            {{ ucfirst($account->type) }}
                        </span>
                    </td>
                    <td class="muted">{{ ucwords(str_replace('_', ' ', $account->sub_type ?? '—')) }}</td>
                    <td><code>{{ $account->currency }}</code></td>
                    <td style="text-align: right; font-weight: 650; font-family: monospace;">
                        {{ number_format((float) $account->current_balance, 2) }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $account->is_active ? 'success' : 'secondary' }}">
                            {{ $account->is_active ? 'Active' : 'Archived' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center; padding: 24px;">No accounts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
