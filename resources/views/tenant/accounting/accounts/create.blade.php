@extends('tenant.layout')

@section('title', 'New Account')

@section('content')
<header>
    <div>
        <h1>Create New Account</h1>
        <div class="muted">Add a custom account to the general ledger</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.accounts.index') }}" class="btn btn-secondary">← Back to Accounts</a>
    </div>
</header>

<div class="panel" style="max-width: 600px;">
    <form method="POST" action="{{ route('tenant.accounting.accounts.store') }}" class="form" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input name="code" label="Account Code" placeholder="e.g. 5040" required autofocus />

        <x-input name="name" label="Account Name" placeholder="e.g. Internet & Telecoms" required />

        <x-input type="select" name="type" label="Account Classification" required>
            <option value="">Select Account Type...</option>
            <option value="asset" {{ old('type') === 'asset' ? 'selected' : '' }}>Asset (e.g. Bank, Cash, Receivables)</option>
            <option value="liability" {{ old('type') === 'liability' ? 'selected' : '' }}>Liability (e.g. Loans, Payables, VAT)</option>
            <option value="equity" {{ old('type') === 'equity' ? 'selected' : '' }}>Equity (e.g. Owner Capital, Retained Earnings)</option>
            <option value="revenue" {{ old('type') === 'revenue' ? 'selected' : '' }}>Revenue (e.g. Sales, Service Income)</option>
            <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense (e.g. Rent, Wages, Supplies)</option>
        </x-input>

        <x-input name="currency" label="Currency" value="KES" maxlength="3" required />

        <x-input type="textarea" name="description" label="Description" rows="3" placeholder="Notes on when to use this account..." />

        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn">Create Account</button>
            <a href="{{ route('tenant.accounting.accounts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
