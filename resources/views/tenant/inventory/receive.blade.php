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
    <form action="{{ route('tenant.inventory.receive.store') }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        <x-input type="select" name="product_id" label="Product" required>
            <option value="">— Select Product —</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ (old('product_id', $selectedProductId) == $p->id) ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->sku }})
                </option>
            @endforeach
        </x-input>

        <x-input type="select" name="shop_id" label="Receiving Shop Location" required>
            <option value="">— Select Shop —</option>
            @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ (old('shop_id', $selectedShopId) == $s->id) ? 'selected' : '' }}>
                    {{ $s->name }} ({{ $s->code }})
                </option>
            @endforeach
        </x-input>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input type="number" step="0.001" min="0.001" name="quantity" label="Quantity Received" required />
            <x-input type="number" step="0.01" min="0" name="cost_price" label="Unit Cost Price (KES)" value="0.00" required />
        </div>

        <x-input name="reference_number" label="Supplier Invoice / Delivery Note #" placeholder="e.g. INV-98421" />

        <x-input type="textarea" name="notes" label="Notes" rows="2" placeholder="Optional notes" />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Receive Stock</button>
        </div>
    </form>
</div>
@endsection
