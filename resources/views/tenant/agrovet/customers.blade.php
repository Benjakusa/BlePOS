@extends('tenant.layout')
@section('title', 'Agrovet Customers')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Farmers & Customers</h1>
    <a href="{{ route('tenant.agrovet.customers.create') }}" class="btn btn-primary">Add Customer</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Phone</th><th>Village</th><th>Credit Limit</th><th>Credit Balance</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($customers as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td>{{ $c->phone }}</td>
                <td>{{ $c->village ?? '—' }}</td>
                <td>{{ number_format($c->credit_limit, 2) }}</td>
                <td><span class="{{ $c->credit_balance > 0 ? 'text-danger font-bold' : '' }}">{{ number_format($c->credit_balance, 2) }}</span></td>
                <td>
                    <a href="{{ route('tenant.agrovet.credit-ledger', $c) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No customers registered.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $customers->links() }}
</div>
@endsection
