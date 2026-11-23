<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $query = InventoryStock::query()->with(['product.category', 'shop']);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->filled('low_stock') && $request->boolean('low_stock')) {
            $query->whereHas('product', function ($p): void {
                $p->whereColumn('inventory_stocks.quantity', '<=', 'products.reorder_level');
            });
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->value();
            $query->whereHas('product', function ($p) use ($q): void {
                $p->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        $stocks = $query->paginate(50)->withQueryString();
        $shops = Shop::query()->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('tenant.inventory.index', [
            'tenant' => $tenant,
            'stocks' => $stocks,
            'shops' => $shops,
            'categories' => $categories,
        ]);
    }

    public function adjustForm(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $products = Product::query()->where('track_stock', true)->where('is_active', true)->orderBy('name')->get();
        $shops = Shop::query()->orderBy('name')->get();

        $selectedProductId = $request->integer('product_id');
        $selectedShopId = $request->integer('shop_id');

        return view('tenant.inventory.adjust', [
            'tenant' => $tenant,
            'products' => $products,
            'shops' => $shops,
            'selectedProductId' => $selectedProductId,
            'selectedShopId' => $selectedShopId,
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('inventory.adjust'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'delta' => ['required', 'numeric', 'not_in:0'],
            'type' => ['required', 'string', 'in:adjustment,waste,damage,return,internal_use'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 403);

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_if((int) $shop->tenant_id !== (int) $tenant->id, 403);

        $this->inventoryService->adjustStock(
            product: $product,
            shop: $shop,
            quantityDelta: (float) $validated['delta'],
            type: $validated['type'],
            user: $request->user(),
            reason: $validated['reason'],
        );

        return redirect()->route('tenant.inventory.index', ['shop_id' => $shop->id])
            ->with('success', "Stock adjusted by {$validated['delta']} for {$product->name}.");
    }

    public function receiveForm(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $products = Product::query()->where('track_stock', true)->where('is_active', true)->orderBy('name')->get();
        $shops = Shop::query()->orderBy('name')->get();

        return view('tenant.inventory.receive', [
            'tenant' => $tenant,
            'products' => $products,
            'shops' => $shops,
            'selectedProductId' => $request->integer('product_id'),
            'selectedShopId' => $request->integer('shop_id'),
        ]);
    }

    public function receive(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('inventory.receive'), 403, 'Insufficient permission.');

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        abort_if((int) $product->tenant_id !== (int) $tenant->id, 403);

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_if((int) $shop->tenant_id !== (int) $tenant->id, 403);

        $this->inventoryService->receiveStock(
            product: $product,
            shop: $shop,
            quantity: (float) $validated['quantity'],
            user: $request->user(),
            referenceId: $validated['reference_number'] ?? null,
        );

        return redirect()->route('tenant.inventory.index', ['shop_id' => $shop->id])
            ->with('success', "Received {$validated['quantity']} units of {$product->name}.");
    }

    public function movements(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('inventory.view'), 403);

        $query = StockMovement::query()->with(['product', 'shop', 'user'])->latest();

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $movements = $query->paginate(50)->withQueryString();
        $shops = Shop::query()->orderBy('name')->get();

        return view('tenant.inventory.movements', [
            'tenant' => $tenant,
            'movements' => $movements,
            'shops' => $shops,
        ]);
    }
}
