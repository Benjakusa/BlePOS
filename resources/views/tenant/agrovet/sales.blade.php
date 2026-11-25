@extends('tenant.layout')
@section('title', 'Agrovet Sales')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Agrovet Sales</h1>
    <a href="{{ route('tenant.agrovet.sales.create') }}" class="btn btn-primary">New Sale</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Sale #</th><th>Customer</th><th>Total</th><th>Method</th></tr>
        </thead>
        <tbody>
            @forelse($sales as $s)
            <tr>
                <td>{{ $s->sold_at }}</td>
                <td>{{ $s->sale_number }}</td>
                <td>{{ $s->customer?->name ?? 'Walk-in' }}</td>
                <td>{{ number_format($s->total_amount, 2) }}</td>
                <td><span class="badge {{ $s->payment_type === 'credit' ? 'badge-warning' : 'badge-info' }}">{{ ucfirst($s->payment_type) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No sales logged.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $sales->links() }}
</div>
@endsection
