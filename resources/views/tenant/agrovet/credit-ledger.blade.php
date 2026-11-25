@extends('tenant.layout')
@section('title', 'Customer Credit Ledger')
@section('content')
<div class="page-header mb-3">
    <h1 class="page-title">Credit Ledger: {{ $customer->name }}</h1>
    <p class="text-muted">Balance: <strong>KES {{ number_format($customer->credit_balance, 2) }}</strong> / Limit: KES {{ number_format($customer->credit_limit, 2) }}</p>
</div>

<div class="grid grid-4 mb-4">
    <div class="card p-3">
        <h4>KES {{ number_format($aging['0_30'], 2) }}</h4>
        <small class="text-muted">0 - 30 Days</small>
    </div>
    <div class="card p-3">
        <h4>KES {{ number_format($aging['31_60'], 2) }}</h4>
        <small class="text-muted">31 - 60 Days</small>
    </div>
    <div class="card p-3">
        <h4>KES {{ number_format($aging['61_90'], 2) }}</h4>
        <small class="text-muted">61 - 90 Days</small>
    </div>
    <div class="card p-3">
        <h4 class="text-danger">KES {{ number_format($aging['over_90'], 2) }}</h4>
        <small class="text-muted">Over 90 Days</small>
    </div>
</div>

<div class="card p-3 mb-4">
    <h4>Record Repayment</h4>
    <form method="POST" action="{{ route('tenant.agrovet.credit.repay') }}" class="d-flex gap-2">
        @csrf
        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
        <input type="number" step="0.01" name="amount" placeholder="Amount" class="form-control" required>
        <select name="method" class="form-control" required>
            <option value="cash">Cash</option>
            <option value="mpesa">M-Pesa</option>
        </select>
        <input type="text" name="reference" placeholder="Reference" class="form-control">
        <button type="submit" class="btn btn-success">Record Repayment</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Type</th><th>Amount</th><th>Method</th><th>Reference</th></tr>
        </thead>
        <tbody>
            @forelse($ledger as $entry)
            <tr>
                <td>{{ $entry->created_at }}</td>
                <td><span class="badge {{ $entry->type === 'repayment' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($entry->type) }}</span></td>
                <td>KES {{ number_format($entry->amount, 2) }}</td>
                <td>{{ $entry->method ?? '—' }}</td>
                <td>{{ $entry->reference ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No ledger transactions recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $ledger->links() }}
</div>
@endsection
