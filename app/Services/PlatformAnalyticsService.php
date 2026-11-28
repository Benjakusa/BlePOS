<?php

namespace App\Services;

use App\Models\Module;
use App\Models\MpesaCredential;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\PlatformEtimsCertification;
use App\Models\PlatformInvoice;
use App\Models\PlatformIpRule;
use App\Models\PlatformPayment;
use App\Models\PlatformSecurityIncident;
use App\Models\PlatformUser;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SupportCase;
use App\Models\Tenant;
use Carbon\Carbon;

class PlatformAnalyticsService
{
    public function __construct(
        private readonly PlatformAuditRecorder $auditRecorder
    ) {}

    /**
     * Compute aggregate growth and tenant lifecycle analytics.
     *
     * @return array<string, mixed>
     */
    public function getGrowthAndLifecycleMetrics(?string $period = '30d'): array
    {
        $startDate = match ($period) {
            '7d' => Carbon::now()->subDays(7),
            '90d' => Carbon::now()->subDays(90),
            '12m' => Carbon::now()->subMonths(12),
            default => Carbon::now()->subDays(30),
        };

        $totalTenants = Tenant::count();
        $newTenants = Tenant::where('created_at', '>=', $startDate)->count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $provisioningFailed = Tenant::where('status', 'provisioning_failed')->count();

        $retentionRate = $totalTenants > 0
            ? round(($activeTenants / $totalTenants) * 100, 1)
            : 100.0;

        $totalShops = Shop::count();
        $totalRegisters = Register::count();

        return [
            'period' => $period,
            'total_tenants' => $totalTenants,
            'new_tenants' => $newTenants,
            'active_tenants' => $activeTenants,
            'suspended_tenants' => $suspendedTenants,
            'provisioning_failed' => $provisioningFailed,
            'retention_rate' => $retentionRate,
            'total_shops' => $totalShops,
            'total_registers' => $totalRegisters,
        ];
    }

    /**
     * Compute financial and subscription revenue analytics using bcmath.
     *
     * @return array<string, mixed>
     */
    public function getRevenueAndBillingMetrics(): array
    {
        // Monthly Recurring Revenue (MRR) from active subscriptions
        $activeSubscriptions = Subscription::query()
            ->with('plan')
            ->whereIn('status', ['active', 'trial'])
            ->get();

        $mrr = '0.00';
        foreach ($activeSubscriptions as $sub) {
            if ($sub->plan) {
                $mrr = bcadd($mrr, (string) $sub->plan->monthly_price, 2);
            }
        }

        // Invoicing & Collections aggregates
        $invoices = PlatformInvoice::all();
        $totalInvoiced = '0.00';
        $totalVat = '0.00';
        $totalCollected = '0.00';
        $totalDue = '0.00';

        foreach ($invoices as $inv) {
            $totalInvoiced = bcadd($totalInvoiced, (string) ($inv->total_amount ?? $inv->total ?? '0.00'), 2);
            $totalVat = bcadd($totalVat, (string) ($inv->tax_amount ?? '0.00'), 2);
            $totalDue = bcadd($totalDue, (string) ($inv->amount_due ?? $inv->balance_due ?? '0.00'), 2);
        }

        $payments = PlatformPayment::where('status', 'completed')->get();
        foreach ($payments as $payment) {
            $totalCollected = bcadd($totalCollected, (string) $payment->amount, 2);
        }

        $collectionRate = bccomp($totalInvoiced, '0.00', 2) > 0
            ? round(((float) $totalCollected / (float) $totalInvoiced) * 100, 1)
            : 100.0;

        return [
            'mrr' => $mrr,
            'arr' => bcmul($mrr, '12', 2),
            'total_invoiced' => $totalInvoiced,
            'total_vat' => $totalVat,
            'total_collected' => $totalCollected,
            'total_due' => $totalDue,
            'collection_rate' => $collectionRate,
            'active_subscriptions_count' => $activeSubscriptions->count(),
        ];
    }

    /**
     * Plan adoption distribution.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPlanDistribution(): array
    {
        $plans = Plan::withCount(['subscriptions' => fn ($q) => $q->whereIn('status', ['active', 'trial'])])->get();

        return $plans->map(function ($plan) {
            $price = (string) ($plan->monthly_price ?? '0.00');
            $planMrr = bcmul($price, (string) $plan->subscriptions_count, 2);

            return [
                'name' => $plan->name,
                'code' => $plan->code,
                'price' => $price,
                'active_subscribers' => $plan->subscriptions_count,
                'estimated_mrr' => $planMrr,
            ];
        })->toArray();
    }

    /**
     * Module catalogue adoption analytics across all tenants.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleAdoptionMetrics(): array
    {
        $totalActiveTenants = Tenant::where('status', 'active')->count();

        $modules = Module::query()
            ->withCount(['entitlements' => fn ($q) => $q->where('is_enabled', true)])
            ->orderByDesc('entitlements_count')
            ->get();

        return $modules->map(function ($module) use ($totalActiveTenants) {
            $adoptionRate = $totalActiveTenants > 0
                ? round(($module->entitlements_count / $totalActiveTenants) * 100, 1)
                : 0.0;

            return [
                'name' => $module->name,
                'slug' => $module->slug,
                'category' => $module->category,
                'is_core' => $module->is_core,
                'enabled_tenants_count' => $module->entitlements_count,
                'adoption_rate' => $adoptionRate,
            ];
        })->toArray();
    }

    /**
     * Industry template distribution among tenants.
     *
     * @return array<string, int>
     */
    public function getIndustryDistribution(): array
    {
        $tenants = Tenant::select('industry')->get();
        $distribution = [
            'retail' => 0,
            'pharmacy' => 0,
            'restaurant' => 0,
            'hotel' => 0,
            'wholesale' => 0,
            'salon' => 0,
            'fuel-station' => 0,
            'other' => 0,
        ];

        foreach ($tenants as $t) {
            $ind = strtolower((string) $t->industry);
            if (array_key_exists($ind, $distribution)) {
                $distribution[$ind]++;
            } else {
                $distribution['other']++;
            }
        }

        return $distribution;
    }

    /**
     * High-level operational health and security metrics.
     *
     * @return array<string, mixed>
     */
    public function getOperationalHealthMetrics(): array
    {
        $totalCases = SupportCase::count();
        $openCases = SupportCase::whereIn('status', ['open', 'in_progress', 'waiting_on_platform'])->count();
        $resolvedCases = SupportCase::whereIn('status', ['resolved', 'closed'])->count();

        $slaMetCases = SupportCase::whereIn('status', ['resolved', 'closed'])
            ->whereNotNull('due_at')
            ->whereColumn('updated_at', '<=', 'due_at')
            ->count();

        $slaRate = $resolvedCases > 0
            ? round(($slaMetCases / $resolvedCases) * 100, 1)
            : 100.0;

        $criticalIncidents30d = PlatformSecurityIncident::query()
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->whereIn('severity', ['high', 'critical'])
            ->count();

        $blockedIpCount = PlatformIpRule::where('rule_type', 'denylist')->where('is_active', true)->count();

        $etimsBlocked = PlatformEtimsCertification::whereIn('status', ['blocked', 'failed', 'pending'])->exists();

        return [
            'support' => [
                'total' => $totalCases,
                'open' => $openCases,
                'resolved' => $resolvedCases,
                'sla_rate' => $slaRate,
            ],
            'security' => [
                'critical_incidents_30d' => $criticalIncidents30d,
                'blocked_ips' => $blockedIpCount,
            ],
            'compliance' => [
                'etims_status' => $etimsBlocked ? 'BLOCKED-ON-CERTIFICATION' : 'CERTIFIED',
            ],
        ];
    }

    /**
     * Export a formula-safe CSV report.
     */
    public function exportReport(string $type, PlatformUser $user): string
    {
        $rows = [];

        switch ($type) {
            case 'revenue':
                $revenue = $this->getRevenueAndBillingMetrics();
                $rows[] = ['Metric', 'Value'];
                $rows[] = ['Monthly Recurring Revenue (KES)', $revenue['mrr']];
                $rows[] = ['Total Invoiced (KES)', $revenue['total_invoiced']];
                $rows[] = ['Total VAT Invoiced 16% (KES)', $revenue['total_vat']];
                $rows[] = ['Total Collected (KES)', $revenue['total_collected']];
                $rows[] = ['Total Due (KES)', $revenue['total_due']];
                $rows[] = ['Collection Efficiency Rate (%)', (string) $revenue['collection_rate']];
                $rows[] = ['Active Subscriptions', (string) $revenue['active_subscriptions_count']];
                break;

            case 'tenants':
                $tenants = Tenant::with(['shops', 'subscriptions.plan'])->get();
                $rows[] = ['Tenant ID', 'Name', 'Slug', 'Status', 'Industry', 'Shops Count', 'Plan'];
                foreach ($tenants as $t) {
                    $activeSub = $t->subscriptions->firstWhere('status', 'active') ?? $t->subscriptions->first();
                    $rows[] = [
                        $t->id,
                        $this->sanitizeCsvValue($t->name),
                        $t->slug,
                        $t->status,
                        $t->industry ?? 'general',
                        $t->shops->count(),
                        $activeSub?->plan?->name ?? 'None',
                    ];
                }
                break;

            case 'modules':
                $modules = $this->getModuleAdoptionMetrics();
                $rows[] = ['Module Name', 'Slug', 'Category', 'Is Core', 'Active Tenants Count', 'Adoption Rate (%)'];
                foreach ($modules as $m) {
                    $rows[] = [
                        $this->sanitizeCsvValue($m['name']),
                        $m['slug'],
                        $m['category'],
                        $m['is_core'] ? 'Yes' : 'No',
                        $m['enabled_tenants_count'],
                        $m['adoption_rate'],
                    ];
                }
                break;

            case 'tenants-comprehensive':
                $tenants = Tenant::with(['shops', 'users', 'subscriptions.plan'])->get();
                $rows[] = ['Tenant ID', 'Tenant Name', 'Code', 'Status', 'Industry Template', 'Shop Count', 'Staff Count', 'Active Plan', 'Total Gross Sales (KES)', 'M-Pesa Vault Configured', 'Created At'];
                foreach ($tenants as $t) {
                    $grossSales = (float) Sale::where('tenant_id', $t->id)->where('status', 'completed')->sum('total_amount');
                    $activeSub = $t->subscriptions->firstWhere('status', 'active') ?? $t->subscriptions->first();
                    $hasMpesa = MpesaCredential::where('tenant_id', $t->id)->exists();

                    $rows[] = [
                        $t->id,
                        $this->sanitizeCsvValue($t->name),
                        $this->sanitizeCsvValue($t->code ?? '—'),
                        ucfirst($t->status),
                        $this->sanitizeCsvValue($t->industry ?? 'general'),
                        $t->shops->count(),
                        $t->users->count(),
                        $this->sanitizeCsvValue($activeSub?->plan?->name ?? 'None'),
                        number_format($grossSales, 2, '.', ''),
                        $hasMpesa ? 'Configured' : 'Not Configured',
                        $t->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
                break;

            case 'billing-invoices':
                $invoices = PlatformInvoice::with('tenant')->orderByDesc('created_at')->get();
                $rows[] = ['Invoice Number', 'Tenant Name', 'Tenant Code', 'Subtotal (KES)', '16% VAT (KES)', 'Total Amount (KES)', 'Amount Paid (KES)', 'Status', 'Due Date', 'Paid At', 'Created At'];
                foreach ($invoices as $inv) {
                    $rows[] = [
                        $this->sanitizeCsvValue($inv->invoice_number),
                        $this->sanitizeCsvValue($inv->tenant?->name ?? 'Unknown'),
                        $this->sanitizeCsvValue($inv->tenant?->code ?? '—'),
                        number_format((float) $inv->subtotal, 2, '.', ''),
                        number_format((float) $inv->tax_amount, 2, '.', ''),
                        number_format((float) $inv->total_amount, 2, '.', ''),
                        number_format((float) $inv->amount_paid, 2, '.', ''),
                        ucfirst($inv->status),
                        $inv->due_date?->format('Y-m-d'),
                        $inv->paid_at?->format('Y-m-d H:i:s') ?? 'Unpaid',
                        $inv->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
                break;

            case 'industry-adoption':
                $industries = $this->getIndustryDistribution();
                $rows[] = ['Industry Template', 'Total Tenants'];
                foreach ($industries as $ind => $count) {
                    $rows[] = [
                        $this->sanitizeCsvValue(ucfirst($ind)),
                        $count,
                    ];
                }
                break;

            case 'audit-trail':
                $logs = PlatformAuditLog::with('actor')->orderByDesc('occurred_at')->limit(500)->get();
                $rows[] = ['Timestamp', 'Actor ID', 'Actor Name', 'Action', 'Target Type', 'Target ID'];
                foreach ($logs as $log) {
                    $rows[] = [
                        $log->occurred_at?->format('Y-m-d H:i:s'),
                        $log->actor_id ?? 'System',
                        $this->sanitizeCsvValue($log->actor?->name ?? 'System'),
                        $this->sanitizeCsvValue($log->action),
                        $this->sanitizeCsvValue($log->target_type),
                        $this->sanitizeCsvValue($log->target_id),
                    ];
                }
                break;

            default:
                $growth = $this->getGrowthAndLifecycleMetrics();
                $rows[] = ['Growth Metric', 'Count'];
                $rows[] = ['Total Tenants', $growth['total_tenants']];
                $rows[] = ['New Tenants (Last 30 Days)', $growth['new_tenants']];
                $rows[] = ['Active Tenants', $growth['active_tenants']];
                $rows[] = ['Suspended Tenants', $growth['suspended_tenants']];
                $rows[] = ['Retention Rate (%)', $growth['retention_rate']];
                $rows[] = ['Total Monitored Shops', $growth['total_shops']];
                $rows[] = ['Total Registers', $growth['total_registers']];
                break;
        }

        $this->auditRecorder->record(
            actorId: $user->id,
            action: 'platform.analytics.report_exported',
            targetType: 'PlatformAnalytics',
            targetId: $type,
            details: [
                'report_type' => $type,
                'row_count' => count($rows),
            ]
        );

        return $this->arrayToCsv($rows);
    }

    /**
     * Prevent CSV formula injection by prepending apostrophe if field starts with formula character.
     */
    private function sanitizeCsvValue(mixed $value): string
    {
        $str = (string) $value;
        if (strlen($str) > 0 && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"])) {
            return "'".$str;
        }

        return $str;
    }

    /**
     * Convert an array of rows to a CSV formatted string.
     */
    private function arrayToCsv(array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return (string) $csv;
    }
}
