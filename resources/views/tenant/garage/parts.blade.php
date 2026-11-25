@extends('tenant.layout')
@section('title', 'Garage Spare Parts')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Spare Parts & Inventory</h1>
    <a href="{{ route('tenant.garage.parts.create') }}" class="btn btn-primary">Add Part</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>SKU</th><th>In Stock</th><th>Reorder Level</th><th>Cost</th><th>Selling</th></tr>
        </thead>
        <tbody>
            @forelse($parts as $p)
            <tr class="{{ $p->quantity_on_hand <= $p->reorder_level && $p->reorder_level > 0 ? 'table-warning' : '' }}">
                <td>{{ $p->name }}</td>
                <td>{{ $p->sku ?? '—' }}</td>
                <td>{{ $p->quantity_on_hand }}</td>
                <td>{{ $p->reorder_level }}</td>
                <td>{{ number_format($p->cost_price, 2) }}</td>
                <td>{{ number_format($p->selling_price, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No spare parts recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $parts->links() }}
</div>
@endsection
