@extends('tenant.layout')

@section('title', 'Record Expense')

@section('content')
<header>
    <div>
        <h1>Record Store Expense</h1>
        <div class="muted">Log an operational payment and post double-entry ledger impact</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.expenses.index') }}" class="btn btn-secondary">← Back to Expenses</a>
    </div>
</header>

<div class="panel" style="max-width: 650px;">
    <form method="POST" action="{{ route('tenant.accounting.expenses.store') }}" class="form" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input type="date" name="expense_date" label="Expense Date" :value="now()->toDateString()" required />

            <x-input type="select" name="category" label="Category" required>
                <option value="utilities" {{ old('category') === 'utilities' ? 'selected' : '' }}>Utilities (Power, Water, Net)</option>
                <option value="rent" {{ old('category') === 'rent' ? 'selected' : '' }}>Rent & Facility</option>
                <option value="supplies" {{ old('category') === 'supplies' ? 'selected' : '' }}>Store Supplies & Packing</option>
                <option value="salaries" {{ old('category') === 'salaries' ? 'selected' : '' }}>Staff Wages & Allowances</option>
                <option value="transport" {{ old('category') === 'transport' ? 'selected' : '' }}>Logistics & Transport</option>
                <option value="maintenance" {{ old('category') === 'maintenance' ? 'selected' : '' }}>Repairs & Maintenance</option>
                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other General Expenses</option>
            </x-input>
        </div>

        <x-input name="description" label="Description" placeholder="e.g. Kenya Power token electricity top-up" required />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input type="number" step="0.01" min="0.01" name="amount" label="Amount (KES)" placeholder="0.00" required />

            <x-input type="select" name="payment_method" label="Payment Method" required>
                <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash from Till</option>
                <option value="mpesa" {{ old('payment_method') === 'mpesa' ? 'selected' : '' }}>M-Pesa Business Till</option>
                <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>Bank Transfer / EFT</option>
                <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Debit / Credit Card</option>
            </x-input>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input type="select" name="account_id" label="Debit Expense Account" required>
                @foreach($expenseAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                        [{{ $acc->code }}] {{ $acc->name }}
                    </option>
                @endforeach
            </x-input>

            <x-input type="select" name="payment_account_id" label="Credit Payment Account" required>
                @foreach($paymentAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                        [{{ $acc->code }}] {{ $acc->name }}
                    </option>
                @endforeach
            </x-input>
        </div>

        <x-input name="receipt_reference" label="Receipt / Voucher Reference" placeholder="e.g. KPLC-REC-93821" />

        <div style="display: flex; gap: 12px; margin-top: 8px;">
            <button type="submit" class="btn">Record & Post Expense</button>
            <a href="{{ route('tenant.accounting.expenses.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
