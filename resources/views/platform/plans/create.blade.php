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
    <form action="{{ route('platform.plans.store') }}" method="POST" class="form" style="max-width: 650px;">
        @csrf
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
            <div>
                <label for="code">Plan Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" required autofocus placeholder="e.g. starter">
                @error('code') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="name">Plan Display Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Starter Retail">
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2">{{ old('description') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
            <div>
                <label for="currency">Currency *</label>
                <input type="text" id="currency" name="currency" value="{{ old('currency', 'KES') }}" required maxlength="3">
            </div>
            <div>
                <label for="monthly_price">Monthly Price *</label>
                <input type="number" step="0.01" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', '0.00') }}" required>
            </div>
            <div>
                <label for="annual_price">Annual Price *</label>
                <input type="number" step="0.01" id="annual_price" name="annual_price" value="{{ old('annual_price', '0.00') }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
            <div>
                <label for="trial_days">Trial Days *</label>
                <input type="number" id="trial_days" name="trial_days" value="{{ old('trial_days', 14) }}" required min="0">
            </div>
            <div>
                <label for="max_shops">Max Shops *</label>
                <input type="number" id="max_shops" name="max_shops" value="{{ old('max_shops', 1) }}" required min="1">
            </div>
            <div>
                <label for="max_users">Max Users *</label>
                <input type="number" id="max_users" name="max_users" value="{{ old('max_users', 5) }}" required min="1">
            </div>
            <div>
                <label for="max_registers">Max Tills *</label>
                <input type="number" id="max_registers" name="max_registers" value="{{ old('max_registers', 2) }}" required min="1">
            </div>
        </div>

        <div>
            <label for="max_warehouses">Max Warehouses</label>
            <input type="number" id="max_warehouses" name="max_warehouses" value="{{ old('max_warehouses', 1) }}" required min="0">
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: auto;">
                <span>Active (available for tenant assignment)</span>
            </label>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit">Save Plan</button>
        </div>
    </form>
</div>
@endsection
