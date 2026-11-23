@extends('tenant.layout')

@section('title', 'Edit ' . $product->name)

@section('content')
<header>
    <div>
        <h1>Edit Product</h1>
        <div class="muted">Update details for {{ $product->name }} (<code>{{ $product->sku }}</code>)</div>
    </div>
    <div>
        <a href="{{ route('tenant.products.show', $product) }}" class="btn btn-secondary">Back to Product</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.products.update', $product) }}" method="POST" class="form" style="max-width: 650px;">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
            <div>
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                @error('name') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="sku">SKU Code *</label>
                <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
                @error('sku') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label for="barcode">Barcode / EAN</label>
                <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}">
                @error('barcode') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— Uncategorized —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
            <div>
                <label for="cost_price">Cost Price (KES) *</label>
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" required>
                @error('cost_price') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="selling_price">Selling Price (KES) *</label>
                <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" required>
                @error('selling_price') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}">
                @error('tax_rate') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div>
                <label for="unit_of_measure">Unit of Measure</label>
                <input type="text" id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', $product->unit_of_measure) }}">
            </div>
            <div>
                <label for="reorder_level">Reorder Level</label>
                <input type="number" step="1" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level) }}">
            </div>
        </div>

        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
        </div>

        <div style="display: flex; gap: 20px;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="track_stock" value="1" {{ old('track_stock', $product->track_stock) ? 'checked' : '' }} style="width: auto;">
                <span>Track Inventory Stock</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} style="width: auto;">
                <span>Active</span>
            </label>
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Update Product</button>
        </div>
    </form>
</div>
@endsection
