@extends('tenant.layout')

@section('title', 'Add Product')

@section('content')
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Add New Product</h2>
            <p class="muted" style="margin-top: 2px;">Register a new SKU and pricing into your tenant catalog</p>
        </div>
        <div>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Products</span>
            </a>
        </div>
    </div>

    <form action="{{ route('tenant.products.store') }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
            <div class="field {{ $errors->has('name') ? 'has-error' : '' }}">
                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder=" " required autofocus>
                <label for="name">Product Name *</label>
                @error('name')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
            <div class="field {{ $errors->has('sku') ? 'has-error' : '' }}">
                <input type="text" id="sku" name="sku" value="{{ old('sku') }}" placeholder=" " required>
                <label for="sku">SKU Code *</label>
                @error('sku')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="field {{ $errors->has('barcode') ? 'has-error' : '' }}">
                <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder=" ">
                <label for="barcode">Barcode / EAN</label>
                @error('barcode')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
            <div class="field {{ $errors->has('category_id') ? 'has-error' : '' }}">
                <select id="category_id" name="category_id">
                    <option value="">— Uncategorized —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <label for="category_id">Category</label>
                @error('category_id')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div class="field {{ $errors->has('cost_price') ? 'has-error' : '' }}">
                <input type="number" step="0.01" id="cost_price" name="cost_price" value="{{ old('cost_price', '0.00') }}" placeholder=" " required>
                <label for="cost_price">Cost Price (KES) *</label>
                @error('cost_price')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
            <div class="field {{ $errors->has('selling_price') ? 'has-error' : '' }}">
                <input type="number" step="0.01" id="selling_price" name="selling_price" value="{{ old('selling_price') }}" placeholder=" " required>
                <label for="selling_price">Selling Price (KES) *</label>
                @error('selling_price')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
            <div class="field {{ $errors->has('tax_rate') ? 'has-error' : '' }}">
                <input type="number" step="0.01" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', '16.00') }}" placeholder=" ">
                <label for="tax_rate">Tax Rate (%)</label>
                @error('tax_rate')
                    <div class="field-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="field">
                <input type="text" id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', 'piece') }}" placeholder=" ">
                <label for="unit_of_measure">Unit of Measure (piece, kg, etc.)</label>
            </div>
            <div class="field">
                <input type="number" step="1" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 5) }}" placeholder=" ">
                <label for="reorder_level">Reorder Level Warning</label>
            </div>
        </div>

        <div class="field">
            <textarea id="description" name="description" placeholder=" ">{{ old('description') }}</textarea>
            <label for="description">Product Description (Optional)</label>
        </div>

        <div style="display: flex; gap: 24px; margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="track_stock" value="1" {{ old('track_stock', true) ? 'checked' : '' }} style="width: auto;">
                <span>Track Inventory Stock</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: auto;">
                <span>Active</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
            <i class="bi bi-check-lg"></i>
            <span>Save Product</span>
        </button>
    </form>
</div>
@endsection
