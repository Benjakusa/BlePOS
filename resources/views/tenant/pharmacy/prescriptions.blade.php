@extends('tenant.layout')
@section('title', 'Prescriptions')
@section('content')
<div class="page-header"><h1>Prescriptions</h1><p class="muted">Prescription status and authorized medicine quantities.</p></div>
<section class="panel"><table><thead><tr><th>Number</th><th>Patient</th><th>Doctor</th><th>Items</th><th>Status</th><th>Expiry</th></tr></thead><tbody>
@foreach($prescriptions as $prescription)<tr><td>{{ $prescription->prescription_number }}</td><td>{{ $prescription->patient_name }}</td><td>{{ $prescription->doctor_name }}</td><td>@foreach($prescription->items as $item){{ $item->product->name }} ({{ $item->dispensed_quantity }}/{{ $item->prescribed_quantity }})@if(!$loop->last), @endif @endforeach</td><td>{{ $prescription->status }}</td><td>{{ $prescription->expiry_date->toDateString() }}</td></tr>@endforeach
</tbody></table>{{ $prescriptions->links() }}</section>
@endsection
