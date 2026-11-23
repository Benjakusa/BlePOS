@extends('tenant.layout')

@section('title', $product->name)

@section('content')
<header>
    <div>
        <h1>{{ $product->name }}</h1>
        <div class="muted">SKU: <code>{{ $product->sku }}</code> | Barcode: {{ $product->barcode ?: 'None' }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('tenant.products.edit', $product) }}" class="btn">Edit Product</a>
        <a href="{{ route('tenant.inventory.adjust', ['product_id' => $product->id]) }}" class="btn btn-secondary">Adjust Stock</a>
        <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Catalog</a>
    </div>
</header>

<div class="grid-cards">
    <div class="stat-card">
        <div class="label">Cost Price</div>
        <div class="value">KES {{ number_format((float) $product->cost_price, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Selling Price</div>
        <div class="value" style="color: #166534;">KES {{ number_format((float) $product->selling_price, 2) }}</div>
    </div>
    <div class="stat-card">
        <div class="label">Gross Margin</div>
        @php
            $margin = (float) $product->selling_price > 0 
                ? (((float) $product->selling_price - (float) $product->cost_price) / (float) $product->selling_price) * 100 
                : 0;
        @endphp
        <div class="value">{{ number_format($margin, 1) }}%</div>
    </div>
    <div class="stat-card">
        <div class="label">Tax Rate</div>
        <div class="value">{{ number_format((float) $product->tax_rate, 1) }}%</div>
    </div>
</div>

<div class="panel" style="margin-bottom: 24px;">
    <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 14px;">Stock by Shop Location</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Shop / Branch</th>
                    <th style="text-align: right;">Quantity on Hand</th>
                    <th>Reorder Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                    <tr>
                        <td><strong>{{ $stock->shop->name }}</strong> ({{ $stock->shop->code }})</td>
                        <td style="text-align: right; font-weight: 700; font-size: 15px;">
                            {{ number_format((float) $stock->quantity, 2) }} {{ $product->unit_of_measure ?? 'piece' }}
                        </td>
                        <td>
                            @if($product->track_stock && (float) $stock->quantity <= (float) ($product->reorder_level ?? 0))
                                <span class="badge badge-warning">Low Stock (&le; {{ $product->reorder_level }})</span>
                            @else
                                <span class="badge badge-success">Sufficient</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('tenant.inventory.adjust', ['product_id' => $product->id, 'shop_id' => $stock->shop_id]) }}" class="btn btn-secondary btn-sm">Adjust</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No inventory records found for any branch.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 12px;">Product Details</h2>
    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px; font-size: 14px;">
        <span class="muted">Category:</span>
        <span>{{ $product->category?->name ?? 'Uncategorized' }}</span>

        <span class="muted">Unit of Measure:</span>
        <span>{{ $product->unit_of_measure ?? 'piece' }}</span>

        <span class="muted">Stock Tracking:</span>
        <span>{{ $product->track_stock ? 'Yes (Inventory tracked per shop)' : 'No (Untracked service/consumable)' }}</span>

        <span class="muted">Status:</span>
        <span>{{ $product->is_active ? 'Active' : 'Inactive' }}</span>

        <span class="muted">Description:</span>
        <span>{{ $product->description ?: '—' }}</span>
    </div>
</div>
@endsection
