@extends('tenant.layout')
@section('title', 'Dispensing Audit')
@section('content')
<div class="page-header"><h1>Dispensing audit</h1><p class="muted">Completed medicine dispensing records linked to sales and batches.</p></div>
<section class="panel"><table><thead><tr><th>Date</th><th>Medicine</th><th>Batch</th><th>Quantity</th><th>Prescription</th><th>Dispensed by</th><th>Pharmacist verified</th></tr></thead><tbody>
@foreach($dispensings as $entry)<tr><td>{{ $entry->created_at->format('Y-m-d H:i') }}</td><td>{{ $entry->product->name }}</td><td>{{ $entry->batch?->batch_number ?? '—' }}</td><td>{{ $entry->quantity }}</td><td>{{ $entry->prescription?->prescription_number ?? '—' }}</td><td>{{ $entry->user->name }}</td><td>{{ $entry->pharmacist_verified ? 'Yes' : 'No' }}</td></tr>@endforeach
</tbody></table>{{ $dispensings->links() }}</section>
@endsection
