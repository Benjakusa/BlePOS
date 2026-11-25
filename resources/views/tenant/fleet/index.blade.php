@extends('tenant.layout')
@section('title', 'Fleet Management')
@section('content')
<div class="page-header">
    <h1 class="page-title">Fleet & Car Hire Operations</h1>
</div>
<div class="grid grid-4 mb-4">
    <div class="card p-3">
        <h3>{{ $totalVehicles }}</h3>
        <p class="text-muted mb-0">Total Fleet</p>
    </div>
    <div class="card p-3">
        <h3>{{ $available }}</h3>
        <p class="text-muted mb-0">Available Vehicles</p>
    </div>
    <div class="card p-3">
        <h3>{{ $activeBookings }}</h3>
        <p class="text-muted mb-0">Active Bookings</p>
    </div>
    <div class="card p-3">
        <h3>{{ $inMaintenance }}</h3>
        <p class="text-muted mb-0">In Maintenance</p>
    </div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('tenant.fleet.vehicles') }}" class="btn btn-primary">Fleet Vehicles</a>
    <a href="{{ route('tenant.fleet.bookings') }}" class="btn btn-outline-primary">Bookings & Rentals</a>
    <a href="{{ route('tenant.fleet.customers') }}" class="btn btn-outline-primary">Customers</a>
    <a href="{{ route('tenant.fleet.drivers') }}" class="btn btn-outline-primary">Drivers</a>
    <a href="{{ route('tenant.fleet.maintenance') }}" class="btn btn-outline-primary">Maintenance Log</a>
</div>
@endsection
