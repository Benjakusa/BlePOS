@extends('tenant.layout')
@section('title', 'Record Agrovet Sale')
@section('content')
<div class="card p-4">
    <h2>Record Sale</h2>
    <form method="POST" action="{{ route('tenant.agrovet.sales.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Customer (Optional for cash/mpesa, required for credit)</label>
            <select name="customer_id" class="form-control">
                <option value="">Walk-in Customer</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->name }} (Credit Limit: {{ number_format($c->credit_limit, 2) }}, Balance: {{ number_format($c->credit_balance, 2) }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-control" required>
                @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit_base }})</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.001" name="quantity" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Unit Price (KES)</label>
            <input type="number" step="0.01" name="unit_price" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Type</label>
            <select name="payment_type" class="form-control" required>
                <option value="cash">Cash</option>
                <option value="mpesa">M-Pesa</option>
                <option value="credit">Credit / Account</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Reference (M-Pesa / Receipt #)</label>
            <input type="text" name="reference" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Complete Sale</button>
    </form>
</div>
@endsection
