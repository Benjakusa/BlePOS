<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\TenantAnalyticsService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $shopId = $request->query('shop_id') ? (int) $request->query('shop_id') : null;
        if ($shopId) {
            $shopBelongs = Shop::where('tenant_id', $tenant->id)->where('id', $shopId)->exists();
            if (! $shopBelongs) {
                $shopId = null;
            }
        }

        $metrics = $this->analyticsService->getBusinessMetrics($tenant, $period, $shopId);
        $shops = Shop::where('tenant_id', $tenant->id)->get();

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

        $shops = Shop::where('tenant_id', $tenant->id)->get();

        return view('tenant.analytics.export', [
            'tenant' => $tenant,
            'shops' => $shops,
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

        $shopId = ! empty($validated['shop_id']) ? (int) $validated['shop_id'] : null;
        if ($shopId) {
            $shopBelongs = Shop::where('tenant_id', $tenant->id)->where('id', $shopId)->exists();
            if (! $shopBelongs) {
                $shopId = null;
            }
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
}
