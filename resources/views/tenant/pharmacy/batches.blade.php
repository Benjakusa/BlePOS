@extends('tenant.layout')
@section('title', 'Medicine Batches')
@section('content')
<div class="page-header"><h1>Medicine batches</h1><p class="muted">Batch balances, expiry dates, quarantine state, and branch.</p></div>
<section class="panel"><table><thead><tr><th>Product</th><th>Shop</th><th>Batch</th><th>Expiry</th><th>Balance</th><th>Quarantined</th></tr></thead><tbody>
@foreach($batches as $batch)<tr><td>{{ $batch->product->name }}</td><td>{{ $batch->shop->name }}</td><td>{{ $batch->batch_number }}</td><td>{{ $batch->expiry_date->toDateString() }}</td><td>{{ $batch->current_quantity }}</td><td>{{ $batch->is_quarantined ? 'Yes' : 'No' }}</td></tr>@endforeach
</tbody></table>{{ $batches->links() }}</section>
@endsection
