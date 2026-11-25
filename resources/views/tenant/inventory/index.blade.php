@extends('tenant.layout')

@section('title', 'Stock Levels')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Inventory Stock Levels</h2>
            <p class="muted" style="margin-top: 2px;">Quantities on hand by product catalog item and shop location</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('tenant.inventory.receive') }}" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-down"></i>
                <span>Receive Stock</span>
            </a>
            <a href="{{ route('tenant.inventory.adjust') }}" class="btn btn-secondary">
                <i class="bi bi-pencil-square"></i>
                <span>Adjust Stock</span>
            </a>
            <a href="{{ route('tenant.inventory.movements') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-right"></i>
                <span>Audit Ledger</span>
            </a>
        </div>
    </div>

    <form action="{{ route('tenant.inventory.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 220px; max-width: 360px;">
            <x-input type="search" name="q" label="Search by name, SKU, or barcode..." :value="request('q')" />
        </div>
        <div style="min-width: 180px;">
            <x-input type="select" name="shop_id" label="Shop Location" :selected="request('shop_id')">
                <option value="">All Shop Locations</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ request('shop_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </x-input>
        </div>
        <div style="display: flex; align-items: center; padding-bottom: 8px;">
            <x-input type="checkbox" name="low_stock" value="1" :checked="(bool) request('low_stock')" label="Low stock only" />
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="bi bi-funnel"></i>
            <span>Filter</span>
        </button>
        @if(request()->hasAny(['q', 'shop_id', 'low_stock']))
            <a href="{{ route('tenant.inventory.index') }}" class="btn btn-ghost" aria-label="Reset filters">
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
                            <td title="{{ $stock->product->name }}">
                                <a href="{{ route('tenant.products.show', $stock->product) }}" class="text-blue">
                                    <strong>{{ $stock->product->name }}</strong>
                                </a>
                            </td>
                            <td title="{{ $stock->product->sku }}">
                                <code style="background: var(--clr-grey-tint); padding: 3px 6px; border-radius: var(--radius-sm); font-size: 12px;">{{ $stock->product->sku }}</code>
                            </td>
                            <td title="{{ $stock->shop->name }}">{{ $stock->shop->name }}</td>
                            <td style="text-align: right; font-weight: 600;" class="{{ $isLow ? 'text-red' : 'text-green' }}" title="{{ number_format((float) $stock->quantity, 2) }}">
                                {{ number_format((float) $stock->quantity, 2) }} <small class="muted">{{ $stock->product->unit_of_measure ?? 'pcs' }}</small>
                            </td>
                            <td style="text-align: right;">{{ $stock->product->reorder_level ?? 0 }}</td>
                            <td>
                                @if($isLow)
                                    <span class="badge badge-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Low Stock
                                    </span>
                                @else
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle"></i>
                                        Normal
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div class="table-actions" style="justify-content: flex-end;">
                                    <a href="{{ route('tenant.inventory.adjust', ['product_id' => $stock->product_id, 'shop_id' => $stock->shop_id]) }}" class="btn-ghost" data-tooltip="Adjust Stock" aria-label="Adjust Stock">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('tenant.inventory.movements', ['product_id' => $stock->product_id, 'shop_id' => $stock->shop_id]) }}" class="btn-ghost" data-tooltip="Movement History" aria-label="Movement History">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 32px;" class="muted">
                                <i class="bi bi-layers" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No stock inventory records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px;">
        {{ $stocks->links() }}
    </div>
</div>
@endsection
