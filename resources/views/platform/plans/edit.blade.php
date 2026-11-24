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
    <form action="{{ route('platform.plans.update', $plan) }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        @method('PUT')

        <x-input name="name" label="Plan Display Name" :value="$plan->name" required />

        <x-input type="textarea" name="description" label="Description" rows="2" :value="$plan->description" />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="number" step="0.01" name="monthly_price" :label="'Monthly Price (' . $plan->currency . ')'" :value="$plan->monthly_price" required />
            <x-input type="number" step="0.01" name="annual_price" :label="'Annual Price (' . $plan->currency . ')'" :value="$plan->annual_price" required />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
            <x-input type="number" name="trial_days" label="Trial Days" :value="$plan->trial_days" required min="0" />
            <x-input type="number" name="max_shops" label="Max Shops" :value="$plan->max_shops" required min="1" />
            <x-input type="number" name="max_users" label="Max Users" :value="$plan->max_users" required min="1" />
            <x-input type="number" name="max_registers" label="Max Tills" :value="$plan->max_registers" required min="1" />
        </div>

        <x-input type="number" name="max_warehouses" label="Max Warehouses" :value="$plan->max_warehouses" required min="0" />

        <x-input type="toggle" name="is_active" value="1" :checked="$plan->is_active" label="Active" />

        <div style="margin-top: 10px;">
            <button type="submit">Update Plan</button>
        </div>
    </form>
</div>
@endsection
