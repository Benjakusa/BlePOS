@extends('tenant.layout')

@section('title', 'Business Dashboard')

@section('content')
<header>
    <div>
        <h1>Dashboard</h1>
        <div class="muted">Overview of operations for {{ $tenant->name }} (Code: {{ $tenant->code }})</div>
    </div>
    <div>
        <a href="{{ route('tenant.pos.checkout') }}" class="btn">Open POS Terminal</a>
    </div>
</header>

<div class="grid-cards">
    <div class="stat-card">
        <div class="label">Today's Sales Count</div>
        <div class="value">{{ number_format($todaySalesCount) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Today's Revenue (KES)</div>
        <div class="value">{{ number_format((float) $todaySalesTotal, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Active Open Shifts</div>
        <div class="value">{{ number_format($openShiftsCount) }}</div>
    </div>
</div>

<div class="panel">
    <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 12px;">Quick Operations</h2>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('tenant.pos.checkout') }}" class="btn">POS Checkout</a>
        <a href="{{ route('tenant.shifts.create') }}" class="btn btn-secondary">Open New Shift</a>
        <a href="{{ route('tenant.products.create') }}" class="btn btn-secondary">Add Product</a>
        <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-secondary">Receive Delivery</a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">View Stock Ledger</a>
    </div>
</div>
@endsection
