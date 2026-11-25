@extends('tenant.layout')
@section('title', 'Fleet Bookings')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Bookings & Rentals</h1>
    <a href="{{ route('tenant.fleet.bookings.create') }}" class="btn btn-primary">New Booking</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Vehicle</th><th>Customer</th><th>Pickup</th><th>Return</th><th>Status</th><th>Total Billed</th></tr>
        </thead>
        <tbody>
            @forelse($bookings as $b)
            <tr>
                <td><strong>{{ $b->vehicle?->plate }}</strong> ({{ $b->vehicle?->make }})</td>
                <td>{{ $b->customer?->name }}</td>
                <td>{{ $b->pickup_at }}</td>
                <td>{{ $b->return_at }}</td>
                <td><span class="badge badge-info">{{ ucfirst($b->status) }}</span></td>
                <td>KES {{ number_format($b->total_billed, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No bookings logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $bookings->links() }}
</div>
@endsection
