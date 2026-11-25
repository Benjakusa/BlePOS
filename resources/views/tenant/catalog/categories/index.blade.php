@extends('tenant.layout')

@section('title', 'Categories')

@section('content')
<header>
    <div>
        <h1>Categories</h1>
        <div class="muted">Manage product hierarchy and categorization</div>
    </div>
    <div>
        <a href="{{ route('tenant.categories.create') }}" class="btn">New Category</a>
    </div>
</header>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Parent Category</th>
                <th>Sort Order</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td><strong>{{ $category->name }}</strong></td>
                    <td><code>{{ $category->slug }}</code></td>
                    <td>{{ $category->parent?->name ?? '—' }}</td>
                    <td>{{ $category->sort_order ?? 0 }}</td>
                    <td>
                        @if($category->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div class="actions" style="justify-content: flex-end;">
                            <a href="{{ route('tenant.categories.edit', $category) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('tenant.categories.destroy', $category) }}" method="POST" data-motion-confirm="Delete this category?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 24px;">No categories found. Click "New Category" to create one.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $categories->links() }}
</div>
@endsection
