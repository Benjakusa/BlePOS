@extends('tenant.layout')

@section('title', 'Edit Product')

@section('content')
<header>
    <div>
        <h1>Edit Product</h1>
        <div class="muted">Update product information for {{ $product->name }}</div>
    </div>
    <div>
        <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.products.update', $product) }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
            <x-input name="name" label="Product Name" :value="$product->name" required />
            <x-input name="sku" label="SKU Code" :value="$product->sku" required />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input name="barcode" label="Barcode / EAN" :value="$product->barcode" />
            <x-input type="select" name="category_id" label="Category">
                <option value="">— Uncategorized —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </x-input>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
            <x-input type="number" step="0.01" name="cost_price" label="Cost Price (KES)" :value="$product->cost_price" required />
            <x-input type="number" step="0.01" name="selling_price" label="Selling Price (KES)" :value="$product->selling_price" required />
            <x-input type="number" step="0.01" name="tax_rate" label="Tax Rate (%)" :value="$product->tax_rate" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <x-input name="unit_of_measure" label="Unit of Measure" :value="$product->unit_of_measure" />
            <x-input type="number" step="1" name="reorder_level" label="Reorder Level" :value="$product->reorder_level" />
        </div>

        <x-input type="textarea" name="description" label="Description" rows="3" :value="$product->description" />

        <div style="display: flex; gap: 20px;">
            <x-input type="toggle" name="track_stock" value="1" :checked="$product->track_stock" label="Track Inventory Stock" />
            <x-input type="toggle" name="is_active" value="1" :checked="$product->is_active" label="Active" />
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Update Product</button>
        </div>
    </form>
</div>
@endsection
