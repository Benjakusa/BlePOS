@extends('tenant.layout')
@section('title', 'Vehicle Service History')
@section('content')
<div class="page-header mb-3">
    <h1 class="page-title">Service History: {{ $vehicle->plate }}</h1>
    <p class="text-muted">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year ?? '—' }}) &middot; Owner: {{ $vehicle->customer?->name }}</p>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Job #</th><th>Status</th><th>Estimate Total</th><th>Paid</th></tr>
        </thead>
        <tbody>
            @forelse($jobs as $j)
            <tr>
                <td>{{ $j->received_at }}</td>
                <td><a href="{{ route('tenant.garage.jobs.show', $j) }}">{{ $j->job_number }}</a></td>
                <td><span class="badge badge-info">{{ ucfirst($j->status) }}</span></td>
                <td>KES {{ number_format($j->estimate?->grand_total ?? 0, 2) }}</td>
                <td>KES {{ number_format($j->payments->sum('amount'), 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No service records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $jobs->links() }}
</div>
@endsection
