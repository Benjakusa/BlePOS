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
    <form method="POST" action="{{ route('tenant.accounting.accounts.store') }}" class="form">
        @csrf

        <label>
            Account Code
            <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. 5040" required>
            @error('code') <span style="color: #dc2626; font-size: 13px;">{{ $message }}</span> @enderror
        </label>

        <label>
            Account Name
            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Internet & Telecoms" required>
            @error('name') <span style="color: #dc2626; font-size: 13px;">{{ $message }}</span> @enderror
        </label>

        <label>
            Account Classification
            <select name="type" required>
                <option value="">Select Account Type...</option>
                <option value="asset" {{ old('type') === 'asset' ? 'selected' : '' }}>Asset (e.g. Bank, Cash, Receivables)</option>
                <option value="liability" {{ old('type') === 'liability' ? 'selected' : '' }}>Liability (e.g. Loans, Payables, VAT)</option>
                <option value="equity" {{ old('type') === 'equity' ? 'selected' : '' }}>Equity (e.g. Owner Capital, Retained Earnings)</option>
                <option value="revenue" {{ old('type') === 'revenue' ? 'selected' : '' }}>Revenue (e.g. Sales, Service Income)</option>
                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense (e.g. Rent, Wages, Supplies)</option>
            </select>
            @error('type') <span style="color: #dc2626; font-size: 13px;">{{ $message }}</span> @enderror
        </label>

        <label>
            Currency
            <input type="text" name="currency" value="{{ old('currency', 'KES') }}" maxlength="3" required>
        </label>

        <label>
            Description
            <textarea name="description" rows="3" placeholder="Notes on when to use this account...">{{ old('description') }}</textarea>
        </label>

        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn">Create Account</button>
            <a href="{{ route('tenant.accounting.accounts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
