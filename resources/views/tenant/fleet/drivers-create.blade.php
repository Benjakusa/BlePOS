@extends('tenant.layout')
@section('title', 'Register Fleet Driver')
@section('content')
<div class="card p-4">
    <h2>Register Driver</h2>
    <form method="POST" action="{{ route('tenant.fleet.drivers.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">License Number</label>
            <input type="text" name="license_number" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save Driver</button>
    </form>
</div>
@endsection
