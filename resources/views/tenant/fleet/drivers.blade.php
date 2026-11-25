@extends('tenant.layout')
@section('title', 'Fleet Drivers')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Fleet Drivers / Operators</h1>
    <a href="{{ route('tenant.fleet.drivers.create') }}" class="btn btn-primary">Add Driver</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Phone</th><th>License #</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($drivers as $d)
            <tr>
                <td>{{ $d->name }}</td>
                <td>{{ $d->phone }}</td>
                <td>{{ $d->license_number ?? '—' }}</td>
                <td><span class="badge {{ $d->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted">No drivers registered.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $drivers->links() }}
</div>
@endsection
