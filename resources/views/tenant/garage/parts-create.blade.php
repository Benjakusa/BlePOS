@extends('tenant.layout')
@section('title', 'Add Spare Part')
@section('content')
<div class="card p-4">
    <h2>Add Spare Part</h2>
    <form method="POST" action="{{ route('tenant.garage.parts.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Part Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">SKU / Code</label>
            <input type="text" name="sku" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity on Hand</label>
            <input type="number" step="0.001" name="quantity_on_hand" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Reorder Level</label>
            <input type="number" step="0.001" name="reorder_level" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cost Price</label>
            <input type="number" step="0.01" name="cost_price" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Selling Price</label>
            <input type="number" step="0.01" name="selling_price" class="form-control" value="0" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Part</button>
    </form>
</div>
@endsection
