@extends('tenant.layout')

@section('title', 'Products Catalog')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Product Catalog</h2>
            <p class="muted" style="margin-top: 2px;">Manage catalog, pricing, SKUs, and stock tracking</p>
        </div>
        <div>
            <a href="{{ route('tenant.products.create') }}" class="btn btn-primary" aria-label="New Product">
                <i class="bi bi-plus-lg"></i>
                <span>New Product</span>
            </a>
        </div>
    </div>

    <form action="{{ route('tenant.products.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 220px; max-width: 360px;">
            <x-input type="search" name="q" label="Search by name, SKU, or barcode..." :value="request('q')" />
        </div>
        <div style="min-width: 180px;">
            <x-input type="select" name="category_id" label="Category" :selected="request('category_id')">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </x-input>
        </div>
        <div style="min-width: 150px;">
            <x-input type="select" name="status" label="Status" :selected="request('status')">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </x-input>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
        </button>
        @if(request()->hasAny(['q', 'category_id', 'status']))
            <a href="{{ route('tenant.products.index') }}" class="btn btn-ghost" aria-label="Reset filters">
                <i class="bi bi-x-circle"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>

    <div class="table-container">
        <div class="table-scroll">
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
                            <td title="{{ $product->name }}">
                                <a href="{{ route('tenant.products.show', $product) }}" class="text-blue">
                                    <strong>{{ $product->name }}</strong>
                                </a>
                                @if($product->unit_of_measure)
                                    <span class="muted" style="font-size: 11px;">({{ $product->unit_of_measure }})</span>
                                @endif
                            </td>
                            <td title="{{ $product->sku }}">
                                <code style="background: var(--clr-grey-tint); padding: 3px 6px; border-radius: var(--radius-sm); font-size: 12px;">{{ $product->sku }}</code>
                            </td>
                            <td title="{{ $product->barcode ?: '—' }}">{{ $product->barcode ?: '—' }}</td>
                            <td title="{{ $product->category?->name ?? '—' }}">{{ $product->category?->name ?? '—' }}</td>
                            <td style="text-align: right;" title="KES {{ number_format((float) $product->cost_price, 2) }}">
                                KES {{ number_format((float) $product->cost_price, 2) }}
                            </td>
                            <td style="text-align: right; font-weight: 600;" class="text-green" title="KES {{ number_format((float) $product->selling_price, 2) }}">
                                KES {{ number_format((float) $product->selling_price, 2) }}
                            </td>
                            <td>
                                @if($product->track_stock)
                                    <span class="badge badge-info">
                                        <i class="bi bi-box-seam"></i>
                                        Tracked
                                    </span>
                                @else
                                    <span class="badge badge-secondary">Untracked</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle"></i>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="bi bi-dash-circle"></i>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="{{ route('tenant.products.show', $product) }}" class="btn-ghost" data-tooltip="View Product" aria-label="View Product">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('tenant.products.edit', $product) }}" class="btn-ghost" data-tooltip="Edit Product" aria-label="Edit Product">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 32px;" class="muted">
                                <i class="bi bi-box-seam" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No products found. Click "New Product" to add your first item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px;">
        {{ $products->links() }}
    </div>
</div>
@endsection
