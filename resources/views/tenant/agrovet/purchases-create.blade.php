@extends('tenant.layout')
@section('title', 'Receive Agrovet Stock')
@section('content')
<div class="card p-4">
    <h2>Receive Stock / Batch</h2>
    <form method="POST" action="{{ route('tenant.agrovet.purchases.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-control" required>
                @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit_base }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Batch Number</label>
            <input type="text" name="batch_number" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.001" name="quantity" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Unit Cost (KES)</label>
            <input type="number" step="0.01" name="unit_cost" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Save Stock</button>
    </form>
</div>
@endsection
