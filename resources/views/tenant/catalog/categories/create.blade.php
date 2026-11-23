@extends('tenant.layout')

@section('title', 'New Category')

@section('content')
<header>
    <div>
        <h1>Create Category</h1>
        <div class="muted">Add a new category to group products</div>
    </div>
    <div>
        <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.categories.store') }}" method="POST" class="form">
        @csrf
        <div>
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="slug">Slug (URL friendly key)</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Auto-generated if left blank">
            @error('slug') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="parent_id">Parent Category</label>
            <select id="parent_id" name="parent_id">
                <option value="">— None (Top Level) —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
            @error('parent_id') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description') <div class="badge badge-danger" style="margin-top: 4px;">{{ $message }}</div> @enderror
        </div>

        <div>
            <label for="sort_order">Sort Order</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
        </div>

        <div>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: auto;">
                <span>Active (visible in POS & catalog)</span>
            </label>
        </div>

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Save Category</button>
        </div>
    </form>
</div>
@endsection
