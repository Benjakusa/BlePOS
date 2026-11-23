<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $categories = Category::query()
            ->with('parent')
            ->orderBy('name')
            ->paginate(50);

        return view('tenant.catalog.categories.index', [
            'categories' => $categories,
            'tenant' => $tenant,
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $parents = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('tenant.catalog.categories.create', [
            'parents' => $parents,
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('catalog.categories.manage'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        // Validate parent belongs to this tenant
        if (! empty($validated['parent_id'])) {
            $parent = Category::query()->findOrFail($validated['parent_id']);
            abort_if((int) $parent->tenant_id !== (int) $tenant->id, 403, 'Parent category does not belong to this business.');
        }

        $validated['slug'] = $validated['slug'] ?? str($validated['name'])->slug()->value();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tenant_id'] = $tenant->id;

        Category::query()->create($validated);

        return redirect()->route('tenant.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 403);

        $parents = Category::query()
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('tenant.catalog.categories.edit', [
            'category' => $category,
            'parents' => $parents,
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 403);
        abort_unless($request->user()?->hasPermission('catalog.categories.manage'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = Category::query()->findOrFail($validated['parent_id']);
            abort_if((int) $parent->tenant_id !== (int) $tenant->id, 403, 'Parent category does not belong to this business.');
            abort_if((int) $validated['parent_id'] === $category->id, 422, 'A category cannot be its own parent.');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('tenant.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $category->tenant_id !== (int) $tenant->id, 403);
        abort_unless($request->user()?->hasPermission('catalog.categories.manage'), 403, 'Insufficient permission.');

        abort_if($category->products()->exists(), 422, 'Cannot delete a category that has products assigned to it.');
        abort_if($category->children()->exists(), 422, 'Cannot delete a category that has sub-categories.');

        $category->delete();

        return redirect()->route('tenant.categories.index')
            ->with('success', 'Category deleted.');
    }
}
