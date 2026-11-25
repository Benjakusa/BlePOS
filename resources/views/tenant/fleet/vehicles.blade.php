@extends('tenant.layout')
@section('title', 'Fleet Vehicles')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Fleet Vehicles</h1>
    <a href="{{ route('tenant.fleet.vehicles.create') }}" class="btn btn-primary">Add Vehicle</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Plate</th><th>Make & Model</th><th>Type</th><th>Daily Rate</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($vehicles as $v)
            <tr>
                <td><strong>{{ $v->plate }}</strong></td>
                <td>{{ $v->make }} {{ $v->model }}</td>
                <td>{{ ucfirst($v->type) }}</td>
                <td>KES {{ number_format($v->daily_rate, 2) }}</td>
                <td><span class="badge {{ $v->status === 'available' ? 'badge-success' : ($v->status === 'maintenance' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($v->status) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No vehicles added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $vehicles->links() }}
</div>
@endsection
