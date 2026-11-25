@extends('tenant.layout')
@section('title', 'Add Mechanic')
@section('content')
<div class="card p-4">
    <h2>Add Mechanic</h2>
    <form method="POST" action="{{ route('tenant.garage.mechanics.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Commission Rate (%)</label>
            <input type="number" step="0.01" name="commission_rate" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Mechanic</button>
    </form>
</div>
@endsection
