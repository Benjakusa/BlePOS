@extends('tenant.layout')
@section('title', 'Pharmacy Operations')
@section('content')
<div class="page-header"><h1>Pharmacy Operations</h1><p class="muted">Batch stock, expiry control, prescriptions, and dispensing history.</p></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if(auth()->user()->hasPermission('inventory.receive'))<section class="panel"><h2>Record medicine batch</h2><form method="post" action="{{ route('tenant.pharmacy.batches.store') }}">@csrf
        <label>Shop<select name="shop_id" required>@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select></label>
        <label>Product<select name="product_id" required>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
        <label>Batch number<input name="batch_number" required></label><label>Expiry date<input type="date" name="expiry_date" required></label>
        <label>Manufactured date<input type="date" name="manufacturing_date"></label><label>Supplier<input name="supplier_name"></label>
        <label>Quantity<input type="number" step="0.0001" min="0.0001" name="initial_quantity" required></label><label>Unit cost<input type="number" step="0.01" min="0" name="unit_cost"></label><button>Record batch</button>
    </form></section>@endif
    @if(auth()->user()->hasPermission('users.manage'))<section class="panel"><h2>Record prescription</h2><form method="post" action="{{ route('tenant.pharmacy.prescriptions.store') }}">@csrf
        <label>Shop<select name="shop_id" required>@foreach($shops as $shop)<option value="{{ $shop->id }}">{{ $shop->name }}</option>@endforeach</select></label>
        <label>Patient name<input name="patient_name" required></label><label>Patient identifier<input name="patient_identifier"></label>
        <label>Doctor name<input name="doctor_name" required></label><label>Doctor license<input name="doctor_license_number" required></label><label>Clinic<input name="hospital_clinic"></label>
        <label>Prescription date<input type="date" name="prescription_date" required></label><label>Expiry date<input type="date" name="expiry_date" required></label>
        <label>Product<select name="product_id" required>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
        <label>Authorized quantity<input type="number" step="0.0001" min="0.0001" name="quantity" required></label><label>Dosage instructions<input name="dosage_instructions" required></label><button>Record prescription</button>
    </form></section>@endif
</div>
<section class="panel"><h2>Batch inventory</h2><table><thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Quantity</th><th>Quarantined</th></tr></thead><tbody>@foreach($batches as $batch)<tr><td>{{ $batch->product->name }}</td><td>{{ $batch->batch_number }}</td><td>{{ $batch->expiry_date->toDateString() }}</td><td>{{ $batch->current_quantity }}</td><td>{{ $batch->is_quarantined ? 'Yes' : 'No' }}</td></tr>@endforeach</tbody></table>{{ $batches->links() }}</section>
<section class="panel"><h2>Prescriptions</h2><table><thead><tr><th>Number</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Expiry</th></tr></thead><tbody>@foreach($prescriptions as $prescription)<tr><td>{{ $prescription->prescription_number }}</td><td>{{ $prescription->patient_name }}</td><td>{{ $prescription->doctor_name }}</td><td>{{ $prescription->status }}</td><td>{{ $prescription->expiry_date->toDateString() }}</td></tr>@endforeach</tbody></table>{{ $prescriptions->links('pagination::bootstrap-5') }}</section>
@endsection
