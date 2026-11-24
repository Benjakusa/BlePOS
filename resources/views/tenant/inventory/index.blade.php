@extends('tenant.layout')

@section('title', 'Stock Levels')

@section('content')
<header>
    <div>
        <h1>Stock Levels</h1>
        <div class="muted">Inventory quantities on hand by product and shop location</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('tenant.inventory.receive') }}" class="btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
            <span>Receive Stock</span>
        </a>
        <a href="{{ route('tenant.inventory.adjust') }}" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            <span>Adjust Stock</span>
        </a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Audit Ledger</span>
        </a>
    </div>
</header>

<div class="panel" style="margin-bottom: 20px;">
    <form action="{{ route('tenant.inventory.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 200px;">
            <label for="q" style="font-size: 12px;">Search Product / SKU / Barcode</label>
            <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="Filter by name, sku, or barcode">
        </div>
        <div style="min-width: 160px;">
            <label for="shop_id" style="font-size: 12px;">Shop Branch</label>
            <select id="shop_id" name="shop_id">
                <option value="">All Shops</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; align-items: center; margin-bottom: 8px;">
            <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} style="width: auto;">
                <span>Low stock only</span>
            </label>
        </div>
        <div>
            <button type="submit" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span>Filter</span>
            </button>
            @if(request()->hasAny(['q', 'shop_id', 'low_stock']))
                <a href="{{ route('tenant.inventory.index') }}" class="btn btn-secondary" style="margin-left: 4px;">Reset</a>
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
                <th>Shop Branch</th>
                <th style="text-align: right;">Quantity on Hand</th>
                <th style="text-align: right;">Reorder Point</th>
                <th>Stock Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stocks as $stock)
                @php
                    $isLow = (float) $stock->quantity <= (float) ($stock->product->reorder_level ?? 0);
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('tenant.products.show', $stock->product) }}"><strong>{{ $stock->product->name }}</strong></a>
                    </td>
                    <td><code>{{ $stock->product->sku }}</code></td>
                    <td>{{ $stock->shop->name }}</td>
                    <td style="text-align: right; font-weight: 700; font-size: 15px; color: {{ $isLow ? '#dc2626' : '#166534' }};">
                        {{ number_format((float) $stock->quantity, 2) }} {{ $stock->product->unit_of_measure ?? 'pcs' }}
                    </td>
                    <td style="text-align: right;">{{ $stock->product->reorder_level ?? 0 }}</td>
                    <td>
                        @if($isLow)
                            <span class="badge badge-warning">Low Stock</span>
                        @else
                            <span class="badge badge-success">Normal</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="actions" style="justify-content: flex-end;">
                            <a href="{{ route('tenant.inventory.adjust', ['product_id' => $stock->product_id, 'shop_id' => $stock->shop_id]) }}" class="btn btn-secondary btn-sm">Adjust</a>
                            <a href="{{ route('tenant.inventory.movements', ['product_id' => $stock->product_id, 'shop_id' => $stock->shop_id]) }}" class="btn btn-secondary btn-sm">History</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 24px;">No inventory records match your criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $stocks->links() }}
</div>
@endsection
