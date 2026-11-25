@extends('tenant.layout')
@section('title', 'Garage Job Cards')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Job Cards</h1>
    <a href="{{ route('tenant.garage.jobs.create') }}" class="btn btn-primary">New Job Card</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Job #</th><th>Vehicle</th><th>Customer</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($jobs as $j)
            <tr>
                <td><strong>{{ $j->job_number }}</strong></td>
                <td>{{ $j->vehicle?->plate }} ({{ $j->vehicle?->make }} {{ $j->vehicle?->model }})</td>
                <td>{{ $j->customer?->name }}</td>
                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $j->status)) }}</span></td>
                <td>
                    <a href="{{ route('tenant.garage.jobs.show', $j) }}" class="btn btn-sm btn-outline-primary">View / Manage</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No job cards.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $jobs->links() }}
</div>
@endsection
