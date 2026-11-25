@extends('tenant.layout')
@section('title', 'Job Card Details')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="page-title">{{ $jobCard->job_number }}</h1>
        <p class="text-muted mb-0">{{ $jobCard->vehicle?->plate }} &middot; {{ $jobCard->customer?->name }} &middot; Status: <strong>{{ ucfirst(str_replace('_', ' ', $jobCard->status)) }}</strong></p>
    </div>
    <form method="POST" action="{{ route('tenant.garage.jobs.advance', $jobCard) }}">
        @csrf
        <button type="submit" class="btn btn-primary">Advance Status</button>
    </form>
</div>

<div class="grid grid-2 gap-4 mb-4">
    <div class="card p-3">
        <h4>Estimate</h4>
        @if($jobCard->estimate)
            <p>Parts: KES {{ number_format($jobCard->estimate->total_parts, 2) }} | Labour: KES {{ number_format($jobCard->estimate->total_labour, 2) }}</p>
            <p><strong>Grand Total: KES {{ number_format($jobCard->estimate->grand_total, 2) }}</strong></p>
            <p>Approved: {{ $jobCard->estimate->is_approved ? 'Yes' : 'No' }}</p>
            @if(!$jobCard->estimate->is_approved)
            <form method="POST" action="{{ route('tenant.garage.estimates.approve', $jobCard) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">Approve Estimate</button>
            </form>
            @endif
        @else
            <form method="POST" action="{{ route('tenant.garage.estimates.store', $jobCard) }}">
                @csrf
                <div class="mb-2">
                    <label>Parts Total</label>
                    <input type="number" step="0.01" name="total_parts" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Labour Total</label>
                    <input type="number" step="0.01" name="total_labour" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Save Estimate</button>
            </form>
        @endif
    </div>

    <div class="card p-3">
        <h4>Payments & Balance</h4>
        <p>Total Paid: KES {{ number_format($totalPaid, 2) }}</p>
        <p>Balance Due: <strong class="{{ $balanceDue > 0 ? 'text-danger' : 'text-success' }}">KES {{ number_format($balanceDue, 2) }}</strong></p>

        <form method="POST" action="{{ route('tenant.garage.payments.store', $jobCard) }}" class="d-flex gap-2">
            @csrf
            <input type="number" step="0.01" name="amount" placeholder="Amount" class="form-control" required>
            <select name="method" class="form-control" required>
                <option value="cash">Cash</option>
                <option value="mpesa">M-Pesa</option>
            </select>
            <input type="text" name="reference" placeholder="Ref" class="form-control">
            <button type="submit" class="btn btn-success">Record Payment</button>
        </form>
    </div>
</div>
@endsection
