<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\MpesaTransaction;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Services\MpesaStkPushService;
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
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 100, 422, 'A valid Idempotency-Key header is required.');

        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:cashier_shifts,id'],
            'register_id' => ['nullable', 'integer', 'exists:registers,id'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.batch_id' => ['nullable', 'integer'],
            'items.*.prescription_id' => ['nullable', 'integer'],
            'items.*.pharmacist_user_id' => ['nullable', 'integer'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,mpesa,card,cheque,credit'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
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
        abort_unless((int) $register->shop_id === (int) $shop->id, 422, 'Register does not belong to this shop.');
        abort_unless($request->user()->canAccessShop($shop), 403, 'You are not assigned to this shop.');
        $hasDiscount = collect($validated['items'])->contains(fn ($item) => bccomp((string) ($item['discount'] ?? '0.00'), '0.00', 2) > 0);
        abort_if($hasDiscount
            && ! $request->user()->hasPermission('pos.discount.apply')
            && ! $request->user()->hasPermission('pos.sale.discount')
            && ! $request->user()->hasPermission('pos.discount.override'), 403, 'Discount permission required.');
        abort_if(collect($validated['payments'])->contains(fn ($payment) => $payment['method'] === 'credit')
            && ! $request->user()->hasPermission('pos.sale.credit'), 403, 'Credit sale permission required.');

        $sale = $this->saleService->createSale(
            shop: $shop,
            register: $register,
            user: $request->user(),
            items: $validated['items'],
            payments: $validated['payments'],
            shift: $shift,
            notes: $validated['notes'] ?? null,
            diningTableId: $validated['dining_table_id'] ?? null,
            idempotencyKey: $idempotencyKey,
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
     * Initiate an M-Pesa STK Push request for a POS checkout.
     */
    public function initiateMpesaStkPush(Request $request, MpesaStkPushService $stkPush): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $validated = $request->validate([
            'pending_checkout_id' => ['required', 'integer', 'exists:sales,id'],
            'phone_number' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $sale = Sale::query()->where('status', 'pending_payment')->findOrFail($validated['pending_checkout_id']);
        abort_if((int) $sale->tenant_id !== (int) $tenant->id, 403);
        abort_if(bccomp((string) $sale->grand_total, '0.00', 2) <= 0, 422, 'Pending checkout has no payable balance.');
        abort_if(MpesaTransaction::query()->where('sale_id', $sale->id)->where('status', 'pending')->where('created_at', '>=', now()->subMinutes(5))->exists(), 409, 'An STK request is already pending for this checkout.');

        try {
            $result = $stkPush->initiate(
                tenantId: $tenant->id,
                branchId: $sale->shop_id,
                phoneNumber: $validated['phone_number'],
                amount: (float) $sale->grand_total,
                accountReference: $sale->sale_number,
                description: 'POS Sale',
                saleId: $sale->id,
            );

            return response()->json($result, $result['status'] === 'initiated' ? 202 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'customer_message' => $e->getMessage(),
            ], 422);
        }
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
