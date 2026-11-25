@extends('tenant.layout')
@section('title', 'Add Agrovet Product')
@section('content')
<div class="card p-4">
    <h2>Add Agrovet Product</h2>
    <form method="POST" action="{{ route('tenant.agrovet.products.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Base Unit (e.g. kg, L, vial)</label>
            <input type="text" name="unit_base" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Reorder Level</label>
            <input type="number" step="0.01" name="reorder_level" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Product</button>
    </form>
</div>
@endsection
