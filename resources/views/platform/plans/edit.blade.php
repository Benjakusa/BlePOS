@extends('platform.layout')

@section('title', 'Edit Plan ' . $plan->name)

@section('content')
<header>
    <div>
        <h1>Edit Plan: {{ $plan->name }}</h1>
        <div class="muted">Code: <code>{{ $plan->code }}</code></div>
    </div>
    <div>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-secondary">Back to Plans</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('platform.plans.update', $plan) }}" method="POST" class="form" style="max-width: 650px;">
        @csrf
        @method('PUT')

        <div>
            <label for="name">Plan Display Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2">{{ old('description', $plan->description) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label for="monthly_price">Monthly Price ({{ $plan->currency }}) *</label>
                <input type="number" step="0.01" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price) }}" required>
            </div>
            <div>
                <label for="annual_price">Annual Price ({{ $plan->currency }}) *</label>
                <input type="number" step="0.01" id="annual_price" name="annual_price" value="{{ old('annual_price', $plan->annual_price) }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
            <div>
                <label for="trial_days">Trial Days *</label>
                <input type="number" id="trial_days" name="trial_days" value="{{ old('trial_days', $plan->trial_days) }}" required min="0">
            </div>
            <div>
                <label for="max_shops">Max Shops *</label>
                <input type="number" id="max_shops" name="max_shops" value="{{ old('max_shops', $plan->max_shops) }}" required min="1">
            </div>
            <div>
                <label for="max_users">Max Users *</label>
                <input type="number" id="max_users" name="max_users" value="{{ old('max_users', $plan->max_users) }}" required min="1">
            </div>
            <div>
                <label for="max_registers">Max Tills *</label>
                <input type="number" id="max_registers" name="max_registers" value="{{ old('max_registers', $plan->max_registers) }}" required min="1">
            </div>
        </div>

        <div>
            <label for="max_warehouses">Max Warehouses</label>
            <input type="number" id="max_warehouses" name="max_warehouses" value="{{ old('max_warehouses', $plan->max_warehouses) }}" required min="0">
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} style="width: auto;">
                <span>Active</span>
            </label>
        </div>

        <div style="margin-top: 10px;">
            <button type="submit">Update Plan</button>
        </div>
    </form>
</div>
@endsection
