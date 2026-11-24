@extends('tenant.layout')

@section('title', 'Chart of Accounts')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Chart of Accounts</h2>
            <p class="muted" style="margin-top: 2px;">Standard double-entry accounts ledger for financial accounting and reporting</p>
        </div>
        <div>
            <a href="{{ route('tenant.accounting.accounts.create') }}" class="btn btn-primary" aria-label="New Account">
                <i class="bi bi-plus-lg"></i>
                <span>New Account</span>
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('tenant.accounting.accounts.index') }}" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 220px; max-width: 360px;">
            <x-input type="search" name="q" label="Search by code or account name..." :value="$q ?? ''" />
        </div>
        <div style="min-width: 180px;">
            <x-input type="select" name="type" label="Account Type" :selected="$type ?? ''">
                <option value="">All Account Types</option>
                <option value="asset" {{ ($type ?? '') === 'asset' ? 'selected' : '' }}>Assets</option>
                <option value="liability" {{ ($type ?? '') === 'liability' ? 'selected' : '' }}>Liabilities</option>
                <option value="equity" {{ ($type ?? '') === 'equity' ? 'selected' : '' }}>Equity</option>
                <option value="revenue" {{ ($type ?? '') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                <option value="expense" {{ ($type ?? '') === 'expense' ? 'selected' : '' }}>Expenses</option>
            </x-input>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
        </button>
        @if(!empty($q) || !empty($type))
            <a href="{{ route('tenant.accounting.accounts.index') }}" class="btn btn-ghost" aria-label="Clear filters">
                <i class="bi bi-x-circle"></i>
                <span>Clear</span>
            </a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
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
                            <td title="{{ $account->code }}">
                                <code style="background: var(--clr-grey-tint); padding: 3px 6px; border-radius: var(--radius-sm); font-size: 12px;">{{ $account->code }}</code>
                            </td>
                            <td title="{{ $account->name }}">
                                <strong>{{ $account->name }}</strong>
                                @if($account->is_system)
                                    <span class="chip" style="margin-left: 6px;">System</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ match($account->type) { 'asset' => 'info', 'liability' => 'warning', 'equity' => 'success', 'revenue' => 'success', default => 'secondary' } }}">
                                    {{ ucfirst($account->type) }}
                                </span>
                            </td>
                            <td title="{{ ucwords(str_replace('_', ' ', $account->sub_type ?? '—')) }}" class="muted">
                                {{ ucwords(str_replace('_', ' ', $account->sub_type ?? '—')) }}
                            </td>
                            <td><code>{{ $account->currency }}</code></td>
                            <td style="text-align: right; font-weight: 600;" class="{{ (float) $account->current_balance < 0 ? 'text-red' : 'text-green' }}" title="{{ number_format((float) $account->current_balance, 2) }}">
                                {{ number_format((float) $account->current_balance, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $account->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    <i class="bi bi-{{ $account->is_active ? 'check-circle' : 'dash-circle' }}"></i>
                                    {{ $account->is_active ? 'Active' : 'Archived' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted" style="text-align: center; padding: 32px;">
                                <i class="bi bi-journal-bookmark" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                No accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
