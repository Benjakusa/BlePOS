@extends('platform.layout')

@section('title', 'Issue Platform Invoice')

@section('content')
<header>
    <div>
        <h1>Issue Platform Invoice</h1>
        <div class="muted">Generate a new platform subscription or services invoice for a tenant</div>
    </div>
    <div>
        <a href="{{ route('platform.invoices.index') }}" class="btn secondary">Back to Invoices</a>
    </div>
</header>

@if ($errors->any())
    <div class="notice" style="border-color: #fca5a5; background: #fef2f2; color: #991b1b;" role="alert">
        <strong>Please check the form errors:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.invoices.store') }}" class="form">
        @csrf

        <label>
            Tenant Organization:
            <select name="tenant_id" required style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                <option value="">Select tenant...</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenantId) == $tenant->id)>
                        {{ $tenant->name }} ({{ $tenant->slug }})
                    </option>
                @endforeach
            </select>
        </label>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <label>
                Payment Due Date:
                <input type="date" name="due_date" value="{{ old('due_date', now()->addDays(14)->format('Y-m-d')) }}" required>
            </label>

            <label>
                Currency:
                <select name="currency" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">
                    <option value="KES" @selected(old('currency') === 'KES')>KES (Kenya Shilling)</option>
                    <option value="USD" @selected(old('currency') === 'USD')>USD (US Dollar)</option>
                </select>
            </label>
        </div>

        <h3 style="margin-top: 16px; margin-bottom: 8px;">Invoice Line Items (16% VAT will be calculated)</h3>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
            <label>
                Description:
                <input type="text" name="items[0][description]" value="{{ old('items.0.description', 'Monthly SaaS Subscription Fee') }}" required>
            </label>
            <label>
                Quantity:
                <input type="number" step="1" min="1" name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}" required>
            </label>
            <label>
                Unit Price (excl. VAT):
                <input type="number" step="0.01" min="0.01" name="items[0][unit_price]" value="{{ old('items.0.unit_price', '5000.00') }}" required>
            </label>
        </div>

        <label style="margin-top: 10px;">
            Notes / Reference:
            <textarea name="notes" rows="3" style="border: 1px solid #b9c9bf; border-radius: 5px; padding: 11px 12px; font: inherit; width: 100%;">{{ old('notes') }}</textarea>
        </label>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <a href="{{ route('platform.invoices.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Issue Invoice</button>
        </div>
    </form>
</div>
@endsection
