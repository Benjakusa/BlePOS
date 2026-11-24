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
    <form action="{{ route('tenant.categories.store') }}" method="POST" class="form" style="max-width: 650px; display: flex; flex-direction: column; gap: 14px;">
        @csrf
        <x-input name="name" label="Category Name" required autofocus />

        <x-input name="slug" label="Slug (URL friendly key)" placeholder="Auto-generated if left blank" />

        <x-input type="select" name="parent_id" label="Parent Category">
            <option value="">— None (Top Level) —</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </x-input>

        <x-input type="textarea" name="description" label="Description" rows="3" />

        <x-input type="number" name="sort_order" label="Sort Order" value="0" min="0" />

        <x-input type="toggle" name="is_active" value="1" :checked="true" label="Active (visible in POS & catalog)" />

        <div style="margin-top: 8px;">
            <button type="submit" class="btn">Save Category</button>
        </div>
    </form>
</div>
@endsection
