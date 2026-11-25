@extends('tenant.layout')
@section('title', 'Add Fleet Customer')
@section('content')
<div class="card p-4">
    <h2>Add Customer</h2>
    <form method="POST" action="{{ route('tenant.fleet.customers.store') }}">
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
            <label class="form-label">National ID / Passport</label>
            <input type="text" name="id_number" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Driver License Number</label>
            <input type="text" name="license_number" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save Customer</button>
    </form>
</div>
@endsection
