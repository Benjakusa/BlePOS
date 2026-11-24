@extends('tenant.layout')

@section('title', 'Adjust Inventory')

@section('content')
<header>
    <div>
        <h1>Stock Adjustment</h1>
        <div class="muted">Record manual corrections, spoilage, shrinkage, or damages with an immutable audit movement</div>
    </div>
    <div>
        <a href="{{ route('tenant.inventory.index') }}" class="btn btn-secondary">Back to Stock</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.inventory.adjust.store') }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        <x-input type="select" name="product_id" label="Product" required>
            <option value="">— Select Product —</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ (old('product_id', $selectedProductId) == $p->id) ? 'selected' : '' }}>
                    {{ $p->name }} ({{ $p->sku }})
                </option>
            @endforeach
        </x-input>

        <x-input type="select" name="shop_id" label="Shop Location" required>
            <option value="">— Select Shop —</option>
            @foreach($shops as $s)
                <option value="{{ $s->id }}" {{ (old('shop_id', $selectedShopId) == $s->id) ? 'selected' : '' }}>
                    {{ $s->name }} ({{ $s->code }})
                </option>
            @endforeach
        </x-input>

        <x-input type="number" step="0.001" name="delta" label="Quantity Change (+ or -)" required placeholder="e.g. -2 for damage, or 5 for recount surplus" helper="Use positive numbers to add stock, negative to subtract stock." />

        <x-input type="select" name="type" label="Adjustment Reason / Type" required>
            <option value="adjustment" {{ old('type') === 'adjustment' ? 'selected' : '' }}>Inventory Count Correction / Recount</option>
            <option value="damage" {{ old('type') === 'damage' ? 'selected' : '' }}>Damaged / Expired Goods</option>
            <option value="waste" {{ old('type') === 'waste' ? 'selected' : '' }}>Waste / Spoilage</option>
            <option value="internal_use" {{ old('type') === 'internal_use' ? 'selected' : '' }}>Internal Business Use</option>
            <option value="return" {{ old('type') === 'return' ? 'selected' : '' }}>Customer / Return</option>
        </x-input>

        <x-input type="textarea" name="reason" label="Explanation / Audit Note" rows="3" required placeholder="Detailed reason for audit compliance" />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Record Adjustment</button>
        </div>
    </form>
</div>
@endsection
