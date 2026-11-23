@extends('tenant.layout')

@section('title', 'Receive Inventory Stock')

@section('content')
<header>
    <div>
        <h1>Receive Stock Delivery</h1>
        <div class="muted">Record incoming shipment from supplier into shop inventory</div>
    </div>
    <div>
        <a href="{{ route('tenant.inventory.index') }}" class="btn btn-secondary">Back to Stock</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.inventory.receive.store') }}" method="POST" class="form">
        @csrf
        <div>
            <label for="product_id">Product *</label>
            <select id="product_id" name="product_id" required>
                <option value="">— Select Product —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ (old('product_id', $selectedProductId) == $p->id) ? 'selected' : '' }}>
                        {{ $p->name }} ({{ $p->sku }})
                    </option>
                @endforeach
            </select>
            @error('product_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="shop_id">Receiving Shop Location *</label>
            <select id="shop_id" name="shop_id" required>
                <option value="">— Select Shop —</option>
                @foreach($shops as $s)
                    <option value="{{ $s->id }}" {{ (old('shop_id', $selectedShopId) == $s->id) ? 'selected' : '' }}>
                        {{ $s->name }} ({{ $s->code }})
                    </option>
                @endforeach
            </select>
            @error('shop_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label for="quantity">Quantity Received *</label>
                <input type="number" step="0.001" id="quantity" name="quantity" value="{{ old('quantity') }}" required min="0.001">
                @error('quantity') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="cost_price">Unit Cost Price (KES) *</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price', '0.00') }}" required min="0">
                @error('cost_price') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label for="reference_number">Supplier Invoice / Delivery Note #</label>
            <input type="text" id="reference_number" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. INV-98421">
        </div>

        <div>
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Receive Stock</button>
        </div>
    </form>
</div>
@endsection
