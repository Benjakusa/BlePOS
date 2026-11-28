<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\TenantAnalyticsService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TenantAnalyticsController extends Controller
{
    public function __construct(private readonly TenantAnalyticsService $analyticsService) {}

    /**
     * Display tenant business metrics and operational dashboard.
     */
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $period = $request->query('period', '30d');
        if (! in_array($period, ['7d', '30d', '90d', '12m'], true)) {
            $period = '30d';
        }

        $shopId = $this->resolveShopId($request, $tenant);

        $metrics = $this->analyticsService->getBusinessMetrics($tenant, $period, $shopId, $request->user());
        $shops = $this->visibleShops($request, $tenant);

        return view('tenant.analytics.index', [
            'tenant' => $tenant,
            'period' => $period,
            'shopId' => $shopId,
            'shops' => $shops,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Dedicated data export center view.
     */
    public function exportCenter(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $shops = $this->visibleShops($request, $tenant);
        $modules = app(ModuleService::class);

        return view('tenant.analytics.export', [
            'tenant' => $tenant,
            'shops' => $shops,
            'canExportShifts' => $request->user()->hasPermission('shifts.view'),
            'canExportInventory' => $request->user()->hasPermission('inventory.view'),
            'canExportProducts' => $request->user()->hasPermission('catalog.products.manage') && $request->user()->hasPermission('accounting.view'),
            'canExportParcels' => $request->user()->hasPermission('parcel.view') && $modules->isModuleAccessible($tenant, 'parcel-delivery'),
            'canExportGym' => $request->user()->hasPermission('gym.view') && $modules->isModuleAccessible($tenant, 'gym'),
            'canExportFleet' => $request->user()->hasPermission('fleet.view') && $modules->isModuleAccessible($tenant, 'fleet'),
            'canExportGarage' => $request->user()->hasPermission('garage.view') && $modules->isModuleAccessible($tenant, 'garage'),
        ]);
    }

    /**
     * Generate and stream formula-safe CSV data export.
     */
    public function export(Request $request): Response
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $validated = $request->validate([
            'export_type' => ['required', 'string', 'in:sales,products,shifts,inventory,parcels,gym,fleet,garage'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'shop_id' => ['nullable', 'integer'],
        ]);

        $shopId = $this->resolveShopId($request, $tenant, $validated['shop_id'] ?? null);
        $user = $request->user();
        if ($validated['export_type'] === 'shifts') {
            abort_unless($user->hasPermission('shifts.view'), 403);
        } elseif ($validated['export_type'] === 'inventory') {
            abort_unless($user->hasPermission('inventory.view'), 403);
        } elseif ($validated['export_type'] === 'products') {
            abort_unless($user->hasPermission('catalog.products.manage') && $user->hasPermission('accounting.view'), 403);
        } elseif ($validated['export_type'] === 'parcels') {
            abort_unless($user->hasPermission('parcel.view'), 403);
        } elseif ($validated['export_type'] === 'gym') {
            abort_unless($user->hasPermission('gym.view'), 403);
        } elseif ($validated['export_type'] === 'fleet') {
            abort_unless($user->hasPermission('fleet.view'), 403);
        } elseif ($validated['export_type'] === 'garage') {
            abort_unless($user->hasPermission('garage.view'), 403);
        }

        $csv = $this->analyticsService->exportData(
            tenant: $tenant,
            type: $validated['export_type'],
            fromDate: $validated['from_date'] ?? null,
            toDate: $validated['to_date'] ?? null,
            shopId: $shopId
        );

        $filename = sprintf('blepos-%s-%s.csv', $validated['export_type'], now()->format('Y-m-d-His'));

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function resolveShopId(Request $request, Tenant $tenant, mixed $requestedShopId = null): ?int
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->isTenantOwner() || $user->hasPermission('shops.all.manage')) {
            if ($requestedShopId && ! Shop::query()->where('tenant_id', $tenant->id)->whereKey($requestedShopId)->exists()) {
                abort(404);
            }

            return $requestedShopId ? (int) $requestedShopId : null;
        }

        $shopIds = $user->shops()->where('shops.tenant_id', $tenant->id)->pluck('shops.id');
        abort_if($shopIds->isEmpty(), 403, 'No branch is assigned to this account.');
        if ($requestedShopId !== null && ! $shopIds->contains((int) $requestedShopId)) {
            abort(403, 'You cannot view reports for that branch.');
        }

        return $requestedShopId ? (int) $requestedShopId : (int) $shopIds->first();
    }

    private function visibleShops(Request $request, Tenant $tenant): Collection
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->isTenantOwner() || $user->hasPermission('shops.all.manage')) {
            return Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();
        }

        return $user->shops()->where('shops.tenant_id', $tenant->id)->orderBy('name')->get();
    }
}
