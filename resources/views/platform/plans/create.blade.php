@extends('platform.layout')

@section('title', 'Create Subscription Plan')

@section('content')
<header>
    <div>
        <h1>Create Subscription Plan</h1>
        <div class="muted">Define pricing, trial duration, and operational quotas</div>
    </div>
    <div>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary">Back to Plans</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('platform.plans.store') }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
            <x-input name="code" label="Plan Code" required autofocus placeholder="e.g. starter" />
            <x-input name="name" label="Plan Display Name" required placeholder="e.g. Starter Retail" />
        </div>

        <x-input type="textarea" name="description" label="Description" rows="2" />

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
            <x-input name="currency" label="Currency" value="KES" required maxlength="3" />
            <x-input type="number" step="0.01" name="monthly_price" label="Monthly Price" value="0.00" required />
            <x-input type="number" step="0.01" name="annual_price" label="Annual Price" value="0.00" required />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
            <x-input type="number" name="trial_days" label="Trial Days" value="14" required min="0" />
            <x-input type="number" name="max_shops" label="Max Shops" value="1" required min="1" />
            <x-input type="number" name="max_users" label="Max Users" value="5" required min="1" />
            <x-input type="number" name="max_registers" label="Max Tills" value="2" required min="1" />
        </div>

        <x-input type="number" name="max_warehouses" label="Max Warehouses" value="1" required min="0" />

        <x-input type="toggle" name="is_active" value="1" :checked="true" label="Active (available for tenant assignment)" />

        <div style="margin-top: 10px;">
            <button type="submit">Save Plan</button>
        </div>
    </form>
</div>
@endsection
