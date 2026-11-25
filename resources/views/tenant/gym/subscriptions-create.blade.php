@extends('tenant.layout')
@section('title', 'New Subscription')
@section('content')
<div class="page-header">
    <h1 class="page-title">New Subscription</h1>
    <a href="{{ route('tenant.gym.subscriptions') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.gym.subscriptions.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Member <span class="text-danger">*</span></label>
                <select name="member_id" class="form-control" required>
                    <option value="">— Select Member —</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ old('member_id') == $m->id ? 'selected' : '' }}>{{ $m->name }} ({{ $m->phone }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Plan <span class="text-danger">*</span></label>
                <select name="plan_id" class="form-control" required>
                    <option value="">— Select Plan —</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ old('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} — {{ number_format($p->price, 2) }} ({{ $p->billing_cycle }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount Paid <span class="text-danger">*</span></label>
                <input type="number" name="amount" class="form-control" step="0.01" min="0" value="{{ old('amount') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                <select name="method" class="form-control" required>
                    <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mpesa" {{ old('method') === 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Reference / Transaction ID</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
            </div>
            <button type="submit" class="btn btn-primary">Create Subscription</button>
        </form>
    </div>
</div>
@endsection
