@extends('tenant.layout')
@section('title', 'Create Booking')
@section('content')
<div class="card p-4">
    <h2>New Fleet Booking</h2>
    <form method="POST" action="{{ route('tenant.fleet.bookings.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Available Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
                @foreach($vehicles as $v)
                <option value="{{ $v->id }}">{{ $v->plate }} ({{ $v->make }} {{ $v->model }}) - Daily: KES {{ number_format($v->daily_rate, 2) }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-control" required>
                @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Driver (Optional)</label>
            <select name="driver_id" class="form-control">
                <option value="">Self Drive</option>
                @foreach($drivers as $d)
                <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->phone }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Pickup Date & Time</label>
            <input type="datetime-local" name="pickup_at" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Expected Return Date & Time</label>
            <input type="datetime-local" name="return_at" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Rate Type</label>
            <select name="rate_type" class="form-control" required>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="custom">Custom</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Agreed Rate (KES)</label>
            <input type="number" step="0.01" name="agreed_rate" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Security Deposit (KES)</label>
            <input type="number" step="0.01" name="deposit_amount" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Confirm Booking</button>
    </form>
</div>
@endsection
