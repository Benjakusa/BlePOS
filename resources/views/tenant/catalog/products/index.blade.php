@extends('tenant.layout')

@section('title', 'Products')

@section('content')
<header>
    <div>
        <h1>Products</h1>
        <div class="muted">Manage catalog, pricing, SKUs, and stock tracking</div>
    </div>
    <div>
        <a href="{{ route('tenant.products.create') }}" class="btn">New Product</a>
    </div>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <form action="{{ route('tenant.products.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label for="q" style="font-size: 12px;">Search Product / SKU / Barcode</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="e.g. Unga or 789...">
        </div>
        <div style="min-width: 160px;">
            <label for="category_id" style="font-size: 12px;">Category</label>
            <select id="category_id" name="category_id">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 140px;">
            <label for="status" style="font-size: 12px;">Status</label>
            <select id="status" name="status">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['q', 'category_id', 'status']))
                <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary" style="margin-left: 4px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Barcode</th>
                <th>Category</th>
                <th style="text-align: right;">Cost Price</th>
                <th style="text-align: right;">Selling Price</th>
                <th>Stock Track</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('tenant.products.show', $product) }}"><strong>{{ $product->name }}</strong></a>
                        @if($product->unit_of_measure)
                            <span class="muted">({{ $product->unit_of_measure }})</span>
                        @endif
                    </td>
                    <td><code>{{ $product->sku }}</code></td>
                    <td>{{ $product->barcode ?: '—' }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td style="text-align: right;">KES {{ number_format((float) $product->cost_price, 2) }}</td>
                    <td style="text-align: right; font-weight: 650; color: #166534;">KES {{ number_format((float) $product->selling_price, 2) }}</td>
                    <td>
                        @if($product->track_stock)
                            <span class="badge badge-info">Tracked</span>
                        @else
                            <span class="badge badge-secondary">Service/Untracked</span>
                        @endif
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="actions" style="justify-content: flex-end;">
                            <a href="{{ route('tenant.products.show', $product) }}" class="btn btn-secondary btn-sm">View</a>
                            <a href="{{ route('tenant.products.edit', $product) }}" class="btn btn-secondary btn-sm">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 24px;">No products found. Click "New Product" to add your first item.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $products->links() }}
</div>
@endsection
