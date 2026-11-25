@extends('tenant.layout')
@section('title', 'Create Job Card')
@section('content')
<div class="card p-4">
    <h2>Open Job Card</h2>
    <div class="mb-3">
        <a href="{{ route('tenant.garage.vehicles.create') }}" class="btn btn-sm btn-outline-secondary">Add New Vehicle</a>
    </div>
    <form method="POST" action="{{ route('tenant.garage.jobs.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
                @foreach($vehicles as $v)
                <option value="{{ $v->id }}">{{ $v->plate }} ({{ $v->make }} {{ $v->model }})</option>
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
            <label class="form-label">Mileage In (KM)</label>
            <input type="number" name="mileage_in" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Issues / Notes</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Create Job Card</button>
    </form>
</div>
@endsection
