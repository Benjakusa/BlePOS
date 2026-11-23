<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $query = Product::query()->with('category');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->value();
            $query->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        $products = $query->orderBy('name')->paginate(50)->withQueryString();

        $categories = Category::query()->orderBy('name')->get();

        return view('tenant.catalog.products.index', [
            'products' => $products,
            'categories' => $categories,
            'tenant' => $tenant,
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.catalog.products.create', [
            'categories' => $categories,
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('catalog.products.manage'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'track_stock' => ['boolean'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['unit_of_measure'] = $validated['unit_of_measure'] ?? 'piece';
        $validated['reorder_level'] = $validated['reorder_level'] ?? 5;
        $validated['track_stock'] = $request->boolean('track_stock', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tax_rate'] = $validated['tax_rate'] ?? '0.00';

        Product::query()->create($validated);

        return redirect()->route('tenant.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 403);

        $stocks = InventoryStock::query()
            ->where('product_id', $product->id)
            ->with('shop')
            ->get();

        return view('tenant.catalog.products.show', [
            'product' => $product->load('category'),
            'stocks' => $stocks,
            'tenant' => $tenant,
        ]);
    }

    public function edit(Product $product): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 403);

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('tenant.catalog.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 403);
        abort_unless($request->user()?->hasPermission('catalog.products.manage'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_of_measure' => ['nullable', 'string', 'max:30'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'track_stock' => ['boolean'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['track_stock'] = $request->boolean('track_stock', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        $product->update($validated);

        return redirect()->route('tenant.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * API endpoint: look up a product by barcode for the POS checkout screen.
     */
    public function lookup(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $barcode = $request->string('barcode')->trim()->value();
        abort_if($barcode === '', 422);

        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($barcode): void {
                $q->where('barcode', $barcode)->orWhere('sku', $barcode);
            })
            ->first();

        if (! $product) {
            return response()->json(['found' => false], 200);
        }

        return response()->json([
            'found' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'selling_price' => $product->selling_price,
                'tax_rate' => $product->tax_rate,
                'unit_of_measure' => $product->unit_of_measure,
                'track_stock' => $product->track_stock,
            ],
        ]);
    }
}
