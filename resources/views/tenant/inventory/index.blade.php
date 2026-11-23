@extends('tenant.layout')

@section('title', 'Stock Levels')

@section('content')
<header>
    <div>
        <h1>Stock Levels</h1>
        <div class="muted">Inventory quantities on hand by product and shop location</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('tenant.inventory.receive') }}" class="btn">Receive Stock</a>
        <a href="{{ route('tenant.inventory.adjust') }}" class="btn btn-secondary">Adjust Stock</a>
        <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">Audit Ledger</a>
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
            <button type="submit" class="btn btn-secondary">Filter</button>
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
