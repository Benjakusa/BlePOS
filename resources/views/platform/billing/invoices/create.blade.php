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

<div class="panel" style="max-width: 680px;">
    <form method="POST" action="{{ route('platform.invoices.store') }}" class="form" style="display: flex; flex-direction: column; gap: 14px;">
        @csrf

        <x-input type="select" name="tenant_id" label="Tenant Organization" required>
            <option value="">Select tenant...</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(old('tenant_id', $selectedTenantId) == $tenant->id)>
                    {{ $tenant->name }} ({{ $tenant->slug }})
                </option>
            @endforeach
        </x-input>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="date" name="due_date" label="Payment Due Date" :value="now()->addDays(14)->format('Y-m-d')" required />
            <x-input type="select" name="currency" label="Currency">
                <option value="KES" @selected(old('currency') === 'KES')>KES (Kenya Shilling)</option>
                <option value="USD" @selected(old('currency') === 'USD')>USD (US Dollar)</option>
            </x-input>
        </div>

        <h3 style="margin-top: 8px; margin-bottom: 0px;">Invoice Line Items (16% VAT will be calculated)</h3>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
            <x-input name="items[0][description]" label="Description" value="Monthly SaaS Subscription Fee" required />
            <x-input type="number" name="items[0][quantity]" label="Quantity" step="1" min="1" value="1" required />
            <x-input type="number" step="0.01" min="0.01" name="items[0][unit_price]" label="Unit Price (excl. VAT)" value="5000.00" required />
        </div>

        <x-input type="textarea" name="notes" label="Notes / Reference" rows="3" />

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <a href="{{ route('platform.invoices.index') }}" class="btn secondary">Cancel</a>
            <button type="submit">Issue Invoice</button>
        </div>
    </form>
</div>
@endsection
