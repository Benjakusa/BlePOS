<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    /**
     * POS checkout screen. Requires an open cashier shift.
     */
    public function checkout(Request $request): View|RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403, 'Cashier permission required.');

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $openShift) {
            return redirect()->route('tenant.shifts.index')
                ->with('warning', 'You must open a cashier shift before processing sales.');
        }

        $registers = Register::query()->get();
        $tables = DiningTable::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tenant.pos.checkout', [
            'tenant' => $tenant,
            'openShift' => $openShift,
            'registers' => $registers,
            'tables' => $tables,
            'categories' => $categories,
        ]);
    }

    /**
     * Process a completed sale. Accepts JSON payload from the POS screen.
     */
    public function processSale(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:cashier_shifts,id'],
            'register_id' => ['nullable', 'integer', 'exists:registers,id'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,mpesa,card,cheque,credit'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        $shift = CashierShift::query()->findOrFail($validated['shift_id']);
        abort_if((int) $shift->tenant_id !== (int) $tenant->id, 403);
        abort_if($shift->status !== 'open', 422, 'Cashier shift is not open.');
        abort_if((int) $shift->user_id !== $request->user()->id, 403, 'Shift does not belong to you.');

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_if((int) $shop->tenant_id !== (int) $tenant->id, 403, 'Shop does not belong to this business.');

        $register = $validated['register_id']
            ? Register::query()->findOrFail($validated['register_id'])
            : Register::query()->where('shop_id', $shop->id)->firstOrFail();

        $sale = $this->saleService->createSale(
            shop: $shop,
            register: $register,
            user: $request->user(),
            items: $validated['items'],
            payments: $validated['payments'],
            shift: $shift,
            notes: $validated['notes'] ?? null,
            diningTableId: $validated['dining_table_id'] ?? null,
        );

        // If table was selected, update table status
        if (! empty($validated['dining_table_id'])) {
            $table = DiningTable::find($validated['dining_table_id']);
            $table?->markAvailable(); // Once paid, table can be freed or marked available
        }

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'grand_total' => $sale->grand_total,
            'change_due' => $sale->change_due,
        ]);
    }

    /**
     * Display a completed sale receipt (printable view).
     */
    public function receipt(Sale $sale): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_if((int) $sale->tenant_id !== (int) $tenant->id, 403);

        return view('tenant.pos.receipt', [
            'sale' => $sale->load(['items.product', 'payments', 'user', 'cashierShift']),
            'tenant' => $tenant,
        ]);
    }

    /**
     * Sales history listing.
     */
    public function sales(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sales.view'), 403);

        $query = Sale::query()->with(['user', 'register'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        $sales = $query->paginate(50)->withQueryString();

        return view('tenant.pos.sales', [
            'sales' => $sales,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Void a sale. Requires supervisor permission.
     */
    public function voidSale(Request $request, Sale $sale): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_if((int) $sale->tenant_id !== (int) $tenant->id, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.void'), 403, 'Supervisor permission required to void a sale.');

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:300'],
        ]);

        $this->saleService->voidSale($sale, $request->user(), $validated['void_reason']);

        return redirect()->route('tenant.pos.sales')
            ->with('success', "Sale #{$sale->sale_number} has been voided.");
    }
}
