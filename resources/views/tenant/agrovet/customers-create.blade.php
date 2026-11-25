@extends('tenant.layout')
@section('title', 'Add Customer')
@section('content')
<div class="card p-4">
    <h2>Register Customer</h2>
    <form method="POST" action="{{ route('tenant.agrovet.customers.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Customer Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Village / Location</label>
            <input type="text" name="village" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Credit Limit (KES)</label>
            <input type="number" step="0.01" name="credit_limit" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Customer</button>
    </form>
</div>
@endsection
