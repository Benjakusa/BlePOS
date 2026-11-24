<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformApproval;
use App\Models\PlatformBackupRun;
use App\Services\ComplianceService;
use App\Services\InfrastructureService;
use App\Services\PlatformAnalyticsService;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityService;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformAnalyticsService $analytics,
        InfrastructureService $infra,
        PlatformSecurityService $security,
        ComplianceService $compliance,
        SupportService $support
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.dashboard.view');

        $user = $request->user('platform');
        $growth = $analytics->getGrowthAndLifecycleMetrics('30d');
        $revenue = $analytics->getRevenueAndBillingMetrics();
        $plans = $analytics->getPlanDistribution();
        $modules = $analytics->getModuleAdoptionMetrics();
        $industries = $analytics->getIndustryDistribution();
        $operational = $analytics->getOperationalHealthMetrics();
        $health = $infra->runAllProbes();
        $migrationStatus = $infra->getMigrationStatus();
        $securityData = $security->getSecurityOverview();
        $complianceData = $compliance->getComplianceOverview();
        $supportData = $support->getSupportSummary();
        $pendingApprovals = PlatformApproval::query()
            ->with('requester:id,name,email')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();
        $recentBackups = PlatformBackupRun::query()
            ->latest('id')
            ->limit(5)
            ->get();

        return view('platform.dashboard', [
            'user' => $user,
            'growth' => $growth,
            'revenue' => $revenue,
            'operational' => $operational,
            'plans' => $plans,
            'topModules' => array_slice($modules, 0, 6),
            'industries' => $industries,
            'health' => $health,
            'migrationStatus' => $migrationStatus,
            'security' => $securityData,
            'compliance' => $complianceData,
            'support' => $supportData,
            'pendingApprovals' => $pendingApprovals,
            'recentBackups' => $recentBackups,
        ]);
    }
}
