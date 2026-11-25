@extends('tenant.layout')
@section('title', 'Add Garage Customer')
@section('content')
<div class="card p-4">
    <h2>Add Customer</h2>
    <form method="POST" action="{{ route('tenant.garage.customers.store') }}">
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
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save Customer</button>
    </form>
</div>
@endsection
