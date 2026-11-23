<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Register;
use App\Models\Shop;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CashierShiftController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $query = CashierShift::query()->with(['shop', 'register', 'user'])->latest('opened_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        $shifts = $query->paginate(25)->withQueryString();
        $shops = Shop::query()->orderBy('name')->get();

        $currentOpenShift = CashierShift::query()
            ->where('user_id', $request->user()?->id)
            ->where('status', 'open')
            ->first();

        return view('tenant.shifts.index', [
            'tenant' => $tenant,
            'shifts' => $shifts,
            'shops' => $shops,
            'currentOpenShift' => $currentOpenShift,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        // If user already has an open shift, redirect to that shift
        $existing = CashierShift::query()
            ->where('user_id', $request->user()?->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return redirect()->route('tenant.shifts.show', $existing)
                ->with('info', 'You already have an active open shift.');
        }

        $shops = Shop::query()->with('registers')->orderBy('name')->get();

        return view('tenant.shifts.create', [
            'tenant' => $tenant,
            'shops' => $shops,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'register_id' => ['required', 'integer', 'exists:registers,id'],
            'opening_float' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_if((int) $shop->tenant_id !== (int) $tenant->id, 403);

        $register = Register::query()->findOrFail($validated['register_id']);
        abort_if((int) $register->shop_id !== (int) $shop->id, 422, 'Register does not belong to selected shop.');

        // Prevent opening if user already has an active open shift
        $existing = CashierShift::query()
            ->where('user_id', $request->user()?->id)
            ->where('status', 'open')
            ->first();

        abort_if($existing !== null, 422, 'You already have an active shift. Please close it first.');

        $shiftNumber = 'SH-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        $shift = CashierShift::query()->create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $request->user()?->id,
            'shift_number' => $shiftNumber,
            'status' => 'open',
            'opening_float' => number_format((float) $validated['opening_float'], 2, '.', ''),
            'cash_in' => '0.00',
            'cash_out' => '0.00',
            'cash_sales' => '0.00',
            'mpesa_sales' => '0.00',
            'card_sales' => '0.00',
            'expected_cash' => number_format((float) $validated['opening_float'], 2, '.', ''),
            'actual_cash' => null,
            'variance' => null,
            'closing_notes' => $validated['notes'] ?? null,
            'opened_at' => now(),
        ]);

        return redirect()->route('tenant.shifts.show', $shift)
            ->with('success', "Shift {$shiftNumber} opened successfully.");
    }

    public function show(CashierShift $shift): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $shift->tenant_id !== (int) $tenant->id, 403);

        $shift->load(['shop', 'register', 'user', 'sales' => function ($q) {
            $q->latest()->limit(50);
        }]);

        return view('tenant.shifts.show', [
            'tenant' => $tenant,
            'shift' => $shift,
        ]);
    }

    public function cashMovement(Request $request, CashierShift $shift): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $shift->tenant_id !== (int) $tenant->id, 403);
        abort_if($shift->status !== 'open', 422, 'Shift is not open.');

        $validated = $request->validate([
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $amountStr = number_format((float) $validated['amount'], 2, '.', '');

        if ($validated['type'] === 'in') {
            $shift->cash_in = bcadd((string) $shift->cash_in, $amountStr, 2);
        } else {
            $shift->cash_out = bcadd((string) $shift->cash_out, $amountStr, 2);
        }

        $shift->expected_cash = $shift->calculateExpectedCash();
        $shift->save();

        return redirect()->route('tenant.shifts.show', $shift)
            ->with('success', 'Cash '.strtoupper($validated['type']).' of KES '.$amountStr.' recorded.');
    }

    public function close(Request $request, CashierShift $shift): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_if((int) $shift->tenant_id !== (int) $tenant->id, 403);
        abort_if($shift->status !== 'open', 422, 'Shift is already closed.');

        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $actualCashStr = number_format((float) $validated['actual_cash'], 2, '.', '');
        $expectedCashStr = $shift->calculateExpectedCash();
        $varianceStr = bcsub($actualCashStr, $expectedCashStr, 2);

        $shift->actual_cash = $actualCashStr;
        $shift->expected_cash = $expectedCashStr;
        $shift->variance = $varianceStr;
        $shift->status = 'closed';
        $shift->closed_at = now();

        if (! empty($validated['closing_notes'])) {
            $shift->closing_notes = trim(($shift->closing_notes ? $shift->closing_notes."\n" : '').'Closing Notes: '.$validated['closing_notes']);
        }

        $shift->save();

        return redirect()->route('tenant.shifts.show', $shift)
            ->with('success', "Shift {$shift->shift_number} closed with variance of KES {$varianceStr}.");
    }
}
