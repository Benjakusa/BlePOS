@extends('platform.layout')

@section('title', 'M-Pesa Refund Review')

@section('content')
<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h1 style="font-size:22px;font-weight:600;margin:0;">M-Pesa POS Refund Review</h1>
        <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Record evidence after the customer refund has been completed outside BlePOS.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@forelse($transactions as $transaction)
    <section class="panel" style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <strong>
                    @if($transaction->sale_id)
                        Sale {{ $transaction->sale?->sale_number ?? $transaction->sale_id }}
                    @else
                        Invoice {{ $transaction->platformPayment?->invoice?->invoice_number ?? $transaction->platform_payment_id }}
                    @endif
                </strong>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">
                    Tenant #{{ $transaction->tenant_id }} · {{ $transaction->amount }} {{ $transaction->provider_receipt ? '· Receipt '.$transaction->provider_receipt : '' }}
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">STK transaction #{{ $transaction->id }} · {{ $transaction->created_at?->toDayDateTimeString() }}</div>
            </div>
            <span class="badge bg-warning text-dark">Refund required</span>
        </div>
        <form method="POST" action="{{ route('platform.mpesa.refunds.record', $transaction) }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:16px;">
            @csrf
            <label>External refund reference
                <input class="form-control" name="refund_reference" required maxlength="120" value="{{ old('refund_reference') }}">
            </label>
            <label>Refund evidence / notes
                <input class="form-control" name="refund_notes" required maxlength="500" value="{{ old('refund_notes') }}">
            </label>
            <div style="display:flex;align-items:end;">
                <button class="btn btn-primary" type="submit">Record completed refund</button>
            </div>
        </form>
    </section>
@empty
    <section class="panel"><p style="margin:0;color:#64748b;">No M-Pesa POS transactions currently need refund review.</p></section>
@endforelse

{{ $transactions->links() }}
@endsection
