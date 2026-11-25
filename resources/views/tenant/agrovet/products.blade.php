@extends('tenant.layout')
@section('title', 'Agrovet Products')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center mb-3">
    <h1 class="page-title">Products</h1>
    <a href="{{ route('tenant.agrovet.products.create') }}" class="btn btn-primary">Add Product</a>
</div>
<div class="card">
    <table class="table">
        <thead>
            <tr><th>Name</th><th>Category</th><th>Unit Base</th><th>Reorder Level</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($products as $p)
            <tr>
                <td>{{ $p->name }}</td>
                <td>{{ $p->category ?? '—' }}</td>
                <td>{{ $p->unit_base }}</td>
                <td>{{ $p->reorder_level }}</td>
                <td><span class="badge {{ $p->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">No products recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $products->links() }}
</div>
@endsection
