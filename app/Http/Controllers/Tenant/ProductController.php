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
use Illuminate\Support\Facades\Storage;
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
            'is_reusable' => ['boolean'],
            'availability_period_hours' => ['nullable', 'required_if:is_reusable,1', 'integer', 'between:1,720'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $trackStock = $request->boolean('track_stock');
        $isReusable = $request->boolean('is_reusable');
        if ($isReusable && ! $trackStock) {
            return back()->withErrors(['track_stock' => 'Reusable products must track stock to become available again.'])->withInput();
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

        $validated['tenant_id'] = $tenant->id;
        $validated['unit_of_measure'] = $validated['unit_of_measure'] ?? 'piece';
        $validated['reorder_level'] = $validated['reorder_level'] ?? 5;
        $validated['track_stock'] = $trackStock;
        $validated['is_reusable'] = $isReusable;
        $validated['availability_period_hours'] = $validated['is_reusable'] ? (int) $validated['availability_period_hours'] : null;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tax_rate'] = $validated['tax_rate'] ?? '0.00';
        if ($tenant->industry === 'pharmacy') {
            $validated['metadata'] = [
                'requires_prescription' => $request->boolean('requires_prescription'),
                'is_controlled_substance' => $request->boolean('is_controlled_substance'),
            ];
        }

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
            'is_reusable' => ['boolean'],
            'availability_period_hours' => ['nullable', 'required_if:is_reusable,1', 'integer', 'between:1,720'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $trackStock = $request->boolean('track_stock');
        $isReusable = $request->boolean('is_reusable');
        if ($isReusable && ! $trackStock) {
            return back()->withErrors(['track_stock' => 'Reusable products must track stock to become available again.'])->withInput();
        }

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

        $validated['track_stock'] = $trackStock;
        $validated['is_reusable'] = $isReusable;
        $validated['availability_period_hours'] = $validated['is_reusable'] ? (int) $validated['availability_period_hours'] : null;
        $validated['is_active'] = $request->boolean('is_active', true);
        if (TenantContext::getTenant()?->industry === 'pharmacy') {
            $validated['metadata'] = [
                'requires_prescription' => $request->boolean('requires_prescription'),
                'is_controlled_substance' => $request->boolean('is_controlled_substance'),
            ];
        }

        $product->update($validated);

        return redirect()->route('tenant.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * API endpoint: look up products by barcode, SKU, or name for the POS checkout screen.
     */
    public function lookup(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->hasPermission('pos.sale.create') || $user->hasPermission('inventory.view') || $user->hasPermission('catalog.products.manage')),
            403,
            'You are not allowed to search the product catalog.',
        );

        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $query = $request->string('q')->trim()->value()
            ?: $request->string('barcode')->trim()->value();

        $category = $request->string('category_id')->trim()->value();

        $productQuery = Product::query()
            ->where('is_active', true)
            ->where(function ($available): void {
                $available->where('track_stock', false)
                    ->orWhere(function ($tracked): void {
                        $tracked->where('track_stock', true)
                            ->whereHas('stocks', fn ($stock) => $stock->whereColumn('quantity', '>', 'reserved_quantity'));
                    });
            });

        if ($category !== '') {
            $productQuery->where('category_id', $category);
        }

        if ($query !== '') {
            $productQuery->where(function ($q) use ($query): void {
                $q->where('barcode', $query)
                    ->orWhere('sku', $query)
                    ->orWhere('name', 'like', "%{$query}%");
            });
        }

        // If specific barcode exact match requested (scanner mode)
        if ($request->has('barcode') && ! $request->has('q')) {
            $product = $productQuery->first();

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
                    'tax_type' => $product->tax_type,
                    'unit_of_measure' => $product->unit_of_measure,
                    'track_stock' => $product->track_stock,
                    'requires_prescription' => (bool) ($product->metadata['requires_prescription'] ?? false),
                    'is_controlled_substance' => (bool) ($product->metadata['is_controlled_substance'] ?? false),
                    'image_url' => $product->imageUrl(),
                ],
            ]);
        }

        // Search list / catalog picker mode (returns up to 40 matches)
        $products = $productQuery->with('category')->orderBy('name')->limit(40)->get();

        return response()->json([
            'found' => $products->isNotEmpty(),
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'selling_price' => $p->selling_price,
                'tax_rate' => $p->tax_rate,
                'tax_type' => $p->tax_type,
                'unit_of_measure' => $p->unit_of_measure,
                'track_stock' => $p->track_stock,
                'requires_prescription' => (bool) ($p->metadata['requires_prescription'] ?? false),
                'is_controlled_substance' => (bool) ($p->metadata['is_controlled_substance'] ?? false),
                'category_name' => $p->category?->name ?? 'General',
                'image_url' => $p->imageUrl(),
            ]),
        ]);
    }
}
