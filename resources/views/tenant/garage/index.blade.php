@extends('tenant.layout')
@section('title', 'Garage Management')
@section('content')
<div class="page-header">
    <h1 class="page-title">Auto Garage Operations</h1>
</div>
<div class="grid grid-4 mb-4">
    <div class="card p-3">
        <h3>{{ $openJobs }}</h3>
        <p class="text-muted mb-0">Open Job Cards</p>
    </div>
    <div class="card p-3">
        <h3>{{ $awaitingApproval }}</h3>
        <p class="text-muted mb-0">Awaiting Approval</p>
    </div>
    <div class="card p-3">
        <h3>{{ $readyForCollection }}</h3>
        <p class="text-muted mb-0">Ready / Completed</p>
    </div>
    <div class="card p-3">
        <h3>{{ $lowStockParts }}</h3>
        <p class="text-muted mb-0">Low Stock Parts</p>
    </div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('tenant.garage.jobs') }}" class="btn btn-primary">Job Cards</a>
    <a href="{{ route('tenant.garage.parts') }}" class="btn btn-outline-primary">Spare Parts</a>
    <a href="{{ route('tenant.garage.customers') }}" class="btn btn-outline-primary">Customers & Vehicles</a>
    <a href="{{ route('tenant.garage.mechanics') }}" class="btn btn-outline-primary">Mechanics</a>
</div>
@endsection
