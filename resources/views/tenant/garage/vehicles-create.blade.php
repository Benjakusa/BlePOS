@extends('tenant.layout')
@section('title', 'Register Vehicle')
@section('content')
<div class="card p-4">
    <h2>Register Vehicle</h2>
    <form method="POST" action="{{ route('tenant.garage.vehicles.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-control" required>
                @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Registration / Plate Number</label>
            <input type="text" name="plate" class="form-control" required placeholder="e.g. KCA 123A">
        </div>
        <div class="mb-3">
            <label class="form-label">Make</label>
            <input type="text" name="make" class="form-control" required placeholder="Toyota">
        </div>
        <div class="mb-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" required placeholder="Hilux">
        </div>
        <div class="mb-3">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" placeholder="2020">
        </div>
        <div class="mb-3">
            <label class="form-label">Color</label>
            <input type="text" name="color" class="form-control" placeholder="White">
        </div>
        <button type="submit" class="btn btn-primary">Save Vehicle</button>
    </form>
</div>
@endsection
