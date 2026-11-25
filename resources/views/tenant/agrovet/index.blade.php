@extends('tenant.layout')
@section('title', 'Agrovet Management')
@section('content')
<div class="page-header">
    <h1 class="page-title">Agrovet Operations</h1>
</div>
<div class="grid grid-4 mb-4">
    <div class="card p-3">
        <h3>{{ $products }}</h3>
        <p class="text-muted mb-0">Active Products</p>
    </div>
    <div class="card p-3">
        <h3>{{ $lowStock }}</h3>
        <p class="text-muted mb-0">Tracked Stock Items</p>
    </div>
    <div class="card p-3">
        <h3>{{ $creditCustomers }}</h3>
        <p class="text-muted mb-0">Customers with Balance</p>
    </div>
    <div class="card p-3">
        <h3>{{ $todaySales }}</h3>
        <p class="text-muted mb-0">Today's Sales</p>
    </div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('tenant.agrovet.products') }}" class="btn btn-outline-primary">Products</a>
    <a href="{{ route('tenant.agrovet.customers') }}" class="btn btn-outline-primary">Customers & Credit</a>
    <a href="{{ route('tenant.agrovet.purchases') }}" class="btn btn-outline-primary">Purchases / Restock</a>
    <a href="{{ route('tenant.agrovet.sales') }}" class="btn btn-primary">Sales Register</a>
</div>
@endsection
