<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\TenantActivityLog;
use App\Models\User;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantActivityController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('users.manage'), 403);

        $period = $request->string('period', 'day')->value();
        abort_unless(in_array($period, ['day', 'month', 'year'], true), 422);
        [$dateField, $format] = match ($period) {
            'month' => ['date_month', 'Y-m'],
            'year' => ['date_year', 'Y'],
            default => ['date_day', 'Y-m-d'],
        };
        $input = $request->validate([$dateField => ['nullable', "date_format:{$format}"]]);
        $date = $input[$dateField] ?? now()->format($format);
        $anchor = CarbonImmutable::createFromFormat($format, $date);
        [$start, $end] = match ($period) {
            'month' => [$anchor->startOfMonth(), $anchor->endOfMonth()],
            'year' => [$anchor->startOfYear(), $anchor->endOfYear()],
            default => [$anchor->startOfDay(), $anchor->endOfDay()],
        };

        $activityCounts = TenantActivityLog::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->select('user_id')
            ->selectRaw('COUNT(*) AS actions_count')
            ->groupBy('user_id')
            ->pluck('actions_count', 'user_id');

        $salesByEmployee = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->select('user_id')
            ->selectRaw('COUNT(*) AS sales_count')
            ->selectRaw('COALESCE(SUM(grand_total), 0) AS sales_total')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $employees = User::query()
            ->with('roles:id,name')
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->map(function (User $employee) use ($activityCounts, $salesByEmployee): array {
                $sales = $salesByEmployee->get($employee->id);

                return [
                    'employee' => $employee,
                    'actions_count' => (int) $activityCounts->get($employee->id, 0),
                    'sales_count' => (int) ($sales?->sales_count ?? 0),
                    'sales_total' => (float) ($sales?->sales_total ?? 0),
                ];
            });

        $activity = TenantActivityLog::query()
            ->with('user:id,name,email')
            ->whereBetween('occurred_at', [$start, $end])
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return view('tenant.activity.index', [
            'tenant' => $tenant,
            'period' => $period,
            'date' => $date,
            'rangeLabel' => match ($period) {
                'month' => $start->format('F Y'),
                'year' => $start->format('Y'),
                default => $start->format('j F Y'),
            },
            'employees' => $employees,
            'activity' => $activity,
            'totals' => [
                'actions' => $employees->sum('actions_count'),
                'sales_count' => $employees->sum('sales_count'),
                'sales_total' => $employees->sum('sales_total'),
            ],
        ]);
    }
}
