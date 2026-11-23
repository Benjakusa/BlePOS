<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $today = now()->startOfDay();

        // Real database aggregates — never fake sample data
        $todaySalesCount = Sale::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->count();

        $todaySalesTotal = Sale::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('grand_total');

        $openShiftsCount = CashierShift::query()
            ->where('status', 'open')
            ->count();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'todaySalesCount' => $todaySalesCount,
            'todaySalesTotal' => $todaySalesTotal,
            'openShiftsCount' => $openShiftsCount,
        ]);
    }
}
