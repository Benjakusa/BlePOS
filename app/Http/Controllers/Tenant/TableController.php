<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\Shop;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableController extends Controller
{
    /**
     * Display restaurant floor plan and table list.
     */
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $query = DiningTable::query()
            ->where('tenant_id', $tenant->id)
            ->with(['shop', 'currentSale']);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('zone')) {
            $query->where('zone', $request->input('zone'));
        }

        $tables = $query->orderBy('zone')->orderBy('name')->get();
        $shops = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();
        $zones = DiningTable::query()->where('tenant_id', $tenant->id)->distinct()->pluck('zone');

        // Floor plan stats
        $stats = [
            'total' => $tables->count(),
            'available' => $tables->where('status', 'available')->count(),
            'occupied' => $tables->where('status', 'occupied')->count(),
            'reserved' => $tables->where('status', 'reserved')->count(),
            'total_seats' => $tables->sum('seating_capacity'),
        ];

        return view('tenant.tables.index', [
            'tenant' => $tenant,
            'tables' => $tables,
            'shops' => $shops,
            'zones' => $zones,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created dining table.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'name' => ['required', 'string', 'max:64'],
            'code' => ['required', 'string', 'max:32', Rule::unique('dining_tables')->where('shop_id', $request->shop_id)],
            'zone' => ['required', 'string', 'max:64'],
            'seating_capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'reserved', 'cleaning'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DiningTable::query()->create([
            'tenant_id' => $tenant->id,
            'shop_id' => $validated['shop_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'zone' => $validated['zone'],
            'seating_capacity' => $validated['seating_capacity'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('tenant.tables.index')
            ->with('success', 'Dining table added to floor layout.');
    }

    /**
     * Update table status (e.g. seated guests, occupied, freed, reserved).
     */
    public function updateStatus(Request $request, DiningTable $table): JsonResponse|RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless((int) $table->tenant_id === (int) $tenant->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'reserved', 'cleaning'])],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $table->notes,
        ];

        if ($validated['status'] === 'occupied') {
            $updateData['occupied_at'] = now();
            $updateData['guest_count'] = $validated['guest_count'] ?? $table->seating_capacity;
        } elseif ($validated['status'] === 'available') {
            $updateData['occupied_at'] = null;
            $updateData['guest_count'] = null;
            $updateData['current_sale_id'] = null;
        }

        $table->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'table' => $table->fresh(),
            ]);
        }

        return redirect()->route('tenant.tables.index')
            ->with('success', "Table {$table->name} updated to {$table->status}.");
    }

    /**
     * Delete a dining table.
     */
    public function destroy(DiningTable $table): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless((int) $table->tenant_id === (int) $tenant->id, 404);

        $table->delete();

        return redirect()->route('tenant.tables.index')
            ->with('success', 'Dining table removed.');
    }
}
