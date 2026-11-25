@extends('tenant.layout')
@section('title', 'Add Fleet Vehicle')
@section('content')
<div class="card p-4">
    <h2>Add Vehicle to Fleet</h2>
    <form method="POST" action="{{ route('tenant.fleet.vehicles.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Plate Number</label>
            <input type="text" name="plate" class="form-control" required placeholder="e.g. KDA 123Z">
        </div>
        <div class="mb-3">
            <label class="form-label">Make</label>
            <input type="text" name="make" class="form-control" required placeholder="Toyota">
        </div>
        <div class="mb-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" required placeholder="Prado">
        </div>
        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-control" required>
                <option value="car">Car / Sedan / SUV</option>
                <option value="motorbike">Motorbike / Boda</option>
                <option value="van">Van</option>
                <option value="truck">Truck / Pickup</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Daily Rate (KES)</label>
            <input type="number" step="0.01" name="daily_rate" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Weekly Rate (KES)</label>
            <input type="number" step="0.01" name="weekly_rate" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Deposit Amount (KES)</label>
            <input type="number" step="0.01" name="deposit_amount" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Vehicle</button>
    </form>
</div>
@endsection
