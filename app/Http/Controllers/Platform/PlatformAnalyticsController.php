<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformAnalyticsService;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PlatformAnalyticsController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformAnalyticsService $analyticsService
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.dashboard.view');

        $period = $request->query('period', '30d');
        if (! in_array($period, ['7d', '30d', '90d', '12m'])) {
            $period = '30d';
        }

        $growth = $analyticsService->getGrowthAndLifecycleMetrics($period);
        $revenue = $analyticsService->getRevenueAndBillingMetrics();
        $plans = $analyticsService->getPlanDistribution();
        $modules = $analyticsService->getModuleAdoptionMetrics();
        $industries = $analyticsService->getIndustryDistribution();
        $operations = $analyticsService->getOperationalHealthMetrics();

        return view('platform.analytics.index', [
            'period' => $period,
            'growth' => $growth,
            'revenue' => $revenue,
            'plans' => $plans,
            'modules' => $modules,
            'industries' => $industries,
            'operations' => $operations,
        ]);
    }

    public function export(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformAnalyticsService $analyticsService
    ): Response {
        $authorizer->ensure($request->user('platform'), 'platform.dashboard.view');

        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:revenue,tenants,modules,growth,tenants-comprehensive,billing-invoices,industry-adoption,audit-trail'],
        ]);

        $csv = $analyticsService->exportReport($validated['report_type'], $request->user('platform'));
        $filename = sprintf('blepos-analytics-%s-%s.csv', $validated['report_type'], now()->format('Y-m-d-His'));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
