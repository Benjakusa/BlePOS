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

    <form action="{{ route('tenant.products.store') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
            <x-input name="name" label="Product Name" required autofocus />
            <x-input name="sku" label="SKU Code" required />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input name="barcode" label="Barcode / EAN" />
            <x-input type="select" name="category_id" label="Category">
                <option value="">— Uncategorized —</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </x-input>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <x-input type="number" step="0.01" name="cost_price" label="Cost Price (KES)" value="0.00" required />
            <x-input type="number" step="0.01" name="selling_price" label="Selling Price (KES)" required />
            <x-input type="number" step="0.01" name="tax_rate" label="Tax Rate (%)" value="16.00" />
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <x-input name="unit_of_measure" label="Unit of Measure" placeholder="e.g. pcs, kg, litre" />
            <x-input type="number" step="1" name="reorder_level" label="Reorder Alert Level" value="10" />
        </div>

        <x-input type="textarea" name="description" label="Product Description (Optional)" rows="3" />

        <x-input type="file" name="image" label="Product Image (Optional)" accept="image/*" helper="PNG, JPG, or WEBP up to 2MB. Shown on POS product cards." />

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: center;">
            <x-input type="toggle" name="is_reusable" value="1" label="Reusable / time-limited item" helper="Rooms and equipment return to availability after their period ends." />
            <x-input type="number" step="1" min="1" max="720" name="availability_period_hours" label="Availability period (hours)" value="24" helper="Stock is restored after this many hours from sale." />
        </div>

        <div style="display: flex; gap: 24px; margin-top: 4px;">
            <x-input type="toggle" name="track_stock" value="1" :checked="true" label="Track Inventory Stock" />
            <x-input type="toggle" name="is_active" value="1" :checked="true" label="Active (Available in POS)" />
            @if(($tenant->industry ?? null) === 'pharmacy')
                <x-input type="toggle" name="requires_prescription" value="1" label="Prescription required" />
                <x-input type="toggle" name="is_controlled_substance" value="1" label="Controlled substance (pharmacist verification required)" />
            @endif
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px;">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i>
                <span>Save Product</span>
            </button>
        </div>
    </form>
</div>
@endsection
