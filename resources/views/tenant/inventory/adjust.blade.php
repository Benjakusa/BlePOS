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
    <form action="{{ route('tenant.inventory.adjust.store') }}" method="POST" class="form">
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
            <label for="shop_id">Shop Location *</label>
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

        <div>
            <label for="delta">Quantity Change (+ or -) *</label>
            <input type="number" step="0.001" id="delta" name="delta" value="{{ old('delta') }}" required placeholder="e.g. -2 for damage, or 5 for recount surplus">
            <span class="muted" style="font-size: 12px;">Use positive numbers to add stock, negative to subtract stock.</span>
            @error('delta') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="type">Adjustment Reason / Type *</label>
            <select id="type" name="type" required>
                <option value="adjustment" {{ old('type') === 'adjustment' ? 'selected' : '' }}>Inventory Count Correction / Recount</option>
                <option value="damage" {{ old('type') === 'damage' ? 'selected' : '' }}>Damaged / Expired Goods</option>
                <option value="waste" {{ old('type') === 'waste' ? 'selected' : '' }}>Waste / Spoilage</option>
                <option value="internal_use" {{ old('type') === 'internal_use' ? 'selected' : '' }}>Internal Business Use</option>
                <option value="return" {{ old('type') === 'return' ? 'selected' : '' }}>Customer / Return</option>
            </select>
            @error('type') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="reason">Explanation / Audit Note *</label>
            <textarea id="reason" name="reason" rows="3" required placeholder="Detailed reason for audit compliance">{{ old('reason') }}</textarea>
            @error('reason') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Record Adjustment</button>
        </div>
    </form>
</div>
@endsection
