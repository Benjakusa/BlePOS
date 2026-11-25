@extends('tenant.layout')

@section('title', 'Add Material')

@section('content')
<div class="card" style="max-width: 640px;">
    <div style="margin-bottom: 24px;">
        <h2 class="section-title" style="font-size: 18px;">Add Print Material</h2>
        <p class="muted" style="margin-top: 2px;">Register a new material for stock tracking</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 16px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.printing.materials.store') }}">
        @csrf

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <x-input
                name="name"
                label="Material Name"
                :value="old('name')"
                required
                placeholder="e.g. Glossy Paper"
            />

            <x-input
                name="unit"
                label="Unit of Measure"
                :value="old('unit')"
                required
                placeholder="e.g. ream, sheet, roll, litre"
            />

            <x-input
                type="number"
                name="quantity_on_hand"
                label="Quantity on Hand"
                :value="old('quantity_on_hand', 0)"
                required
                min="0"
                step="0.001"
            />

            <x-input
                type="number"
                name="reorder_level"
                label="Reorder Level"
                :value="old('reorder_level', 0)"
                required
                min="0"
                step="0.001"
            />

            <x-input
                type="number"
                name="cost_per_unit"
                label="Cost per Unit (KES)"
                :value="old('cost_per_unit', 0)"
                required
                min="0"
                step="0.01"
            />

            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Material</span>
                </button>
                <a href="{{ route('tenant.printing.materials') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
