@extends('tenant.layout')
@section('title', 'Agrovet Purchases')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Stock In / Purchases</h1>
    <a href="{{ route('tenant.agrovet.purchases.create') }}" class="btn btn-primary">Receive Stock</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Product</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Unit Cost</th><th>Total</th></tr>
        </thead>
        <tbody>
            @forelse($purchases as $p)
            <tr>
                <td>{{ $p->purchased_at }}</td>
                <td>{{ $p->product?->name }}</td>
                <td>{{ $p->batch_number ?? '—' }}</td>
                <td>{{ $p->expiry_date ?? '—' }}</td>
                <td>{{ $p->quantity }}</td>
                <td>{{ number_format($p->unit_cost, 2) }}</td>
                <td>{{ number_format($p->total_cost, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted">No purchases logged.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $purchases->links() }}
</div>
@endsection
