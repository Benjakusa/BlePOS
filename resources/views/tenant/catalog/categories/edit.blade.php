@extends('tenant.layout')

@section('title', 'Edit Category')

@section('content')
<header>
    <div>
        <h1>Edit Category</h1>
        <div class="muted">Update category details for {{ $category->name }}</div>
    </div>
    <div>
        <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
</header>

<div class="panel">
    <form action="{{ route('tenant.categories.update', $category) }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        @method('PUT')

        <x-input name="name" label="Category Name" :value="$category->name" required />

        <x-input name="slug" label="Slug" :value="$category->slug" />

        <x-input type="select" name="parent_id" label="Parent Category">
            <option value="">— None (Top Level) —</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </x-input>

        <x-input type="textarea" name="description" label="Description" rows="3" :value="$category->description" />

        <x-input type="number" name="sort_order" label="Sort Order" :value="$category->sort_order" min="0" />

        <x-input type="toggle" name="is_active" value="1" :checked="$category->is_active" label="Active" />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Update Category</button>
        </div>
    </form>
</div>
@endsection
