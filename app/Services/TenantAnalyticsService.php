<?php

namespace App\Services;

use App\Models\AgrovetCustomer;
use App\Models\AgrovetSale;
use App\Models\CashierShift;
use App\Models\FleetBooking;
use App\Models\FleetVehicle;
use App\Models\GarageJobCard;
use App\Models\GymCheckin;
use App\Models\GymMember;
use App\Models\GymSubscription;
use App\Models\InventoryStock;
use App\Models\Parcel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModuleService;
use Carbon\Carbon;

class TenantAnalyticsService
{
    public function __construct(private readonly ?ModuleService $moduleService = null) {}

    protected function getModuleService(): ModuleService
    {
        return $this->moduleService ?? app(ModuleService::class);
    }
    /**
     * Compute comprehensive business metrics for a tenant within a given period.
     */
    public function getBusinessMetrics(Tenant $tenant, string $period = '30d', ?int $shopId = null, ?User $user = null): array
    {
        $startDate = $this->resolveStartDate($period);

        $salesQuery = Sale::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId));

        $totalSales = (float) (clone $salesQuery)->sum('grand_total');
        $netSales = (float) (clone $salesQuery)->sum('subtotal');
        $totalTax = (float) (clone $salesQuery)->sum('tax_total');
        $totalDiscount = (float) (clone $salesQuery)->sum('discount_total');
        $orderCount = (clone $salesQuery)->count();
        $avgTicket = $orderCount > 0 ? round($totalSales / $orderCount, 2) : 0.0;

        // Payment breakdown
        $saleIds = (clone $salesQuery)->pluck('id');
        $paymentsQuery = SalePayment::query()->whereIn('sale_id', $saleIds);

        $paymentMethods = [
            'cash' => (float) (clone $paymentsQuery)->where('payment_method', 'cash')->sum('amount'),
            'mpesa' => (float) (clone $paymentsQuery)->where('payment_method', 'mpesa')->sum('amount'),
            'card' => (float) (clone $paymentsQuery)->where('payment_method', 'card')->sum('amount'),
            'credit' => (float) (clone $paymentsQuery)->where('payment_method', 'credit')->sum('amount'),
        ];
        $totalPaid = array_sum($paymentMethods);

        // Daily trend
        $dailyTrends = (clone $salesQuery)
            ->selectRaw('date(created_at) as sale_date, count(*) as count, sum(grand_total) as total')
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'asc')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->sale_date,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->toArray();

        // Top selling products
        $topProducts = SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->selectRaw('product_id, sum(quantity) as units_sold, sum(total_price) as gross_revenue')
            ->groupBy('product_id')
            ->orderByDesc('gross_revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $product = Product::withoutGlobalScopes()->find($item->product_id);

                return [
                    'product_id' => $item->product_id,
                    'name' => $product?->name ?? 'Unknown Product',
                    'sku' => $product?->sku ?? '—',
                    'units_sold' => (float) $item->units_sold,
                    'gross_revenue' => (float) $item->gross_revenue,
                ];
            });

        // Cashier shift performance
        $cashierStats = CashierShift::query()
            ->where('tenant_id', $tenant->id)
            ->when($startDate, fn ($q) => $q->where('opened_at', '>=', $startDate))
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($shifts, $userId) {
                $user = User::withoutGlobalScopes()->find($userId);

                return [
                    'user_id' => $userId,
                    'name' => $user?->name ?? 'Unknown Cashier',
                    'shift_count' => $shifts->count(),
                    'total_sales' => (float) $shifts->sum('cash_sales'),
                    'cash_variance' => (float) $shifts->sum('variance'),
                ];
            })
            ->values();

        // Branch comparisons (if multi-shop)
        $branchStats = Shop::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->map(function ($shop) use ($startDate) {
                $sales = Sale::query()
                    ->where('shop_id', $shop->id)
                    ->where('status', 'completed')
                    ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                    ->sum('grand_total');

                return [
                    'shop_id' => $shop->id,
                    'name' => $shop->name,
                    'code' => $shop->code,
                    'sales_total' => (float) $sales,
                ];
            });

        // Industry Highlights
        $industryStats = $this->getIndustrySpecificMetrics($tenant, $user);

        return [
            'period' => $period,
            'total_sales' => $totalSales,
            'net_sales' => $netSales,
            'total_tax' => $totalTax,
            'total_discount' => $totalDiscount,
            'order_count' => $orderCount,
            'avg_ticket' => $avgTicket,
            'payments' => $paymentMethods,
            'total_paid' => $totalPaid,
            'daily_trends' => $dailyTrends,
            'top_products' => $topProducts,
            'cashier_stats' => $cashierStats,
            'branch_stats' => $branchStats,
            'industry_stats' => $industryStats,
        ];
    }

    /**
     * Compute operational metrics tailored to tenant's active industry vertical.
     */
    public function getIndustrySpecificMetrics(Tenant $tenant, ?User $user = null): array
    {
        $metrics = [];
        $modules = $this->getModuleService();

        // Parcels
        if ($modules->isModuleAccessible($tenant, 'parcel-delivery') && (! $user || $user->hasPermission('parcel.view'))) {
            $metrics['parcels'] = [
                'total_received' => Parcel::where('tenant_id', $tenant->id)->count(),
                'in_transit' => Parcel::where('tenant_id', $tenant->id)->where('status', 'in_transit')->count(),
                'arrived' => Parcel::where('tenant_id', $tenant->id)->where('status', 'arrived')->count(),
                'delivered' => Parcel::where('tenant_id', $tenant->id)->where('status', 'delivered')->count(),
                'total_fees' => (float) Parcel::where('tenant_id', $tenant->id)->sum('fee'),
            ];
        }

        // Gym
        if ($modules->isModuleAccessible($tenant, 'gym') && (! $user || $user->hasPermission('gym.view'))) {
            $metrics['gym'] = [
                'total_members' => GymMember::where('tenant_id', $tenant->id)->count(),
                'active_subscriptions' => GymSubscription::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'frozen_subscriptions' => GymSubscription::where('tenant_id', $tenant->id)->where('status', 'frozen')->count(),
                'today_checkins' => GymCheckin::where('tenant_id', $tenant->id)->whereDate('checked_in_at', today())->count(),
            ];
        }

        // Fleet
        if ($modules->isModuleAccessible($tenant, 'fleet') && (! $user || $user->hasPermission('fleet.view'))) {
            $metrics['fleet'] = [
                'total_vehicles' => FleetVehicle::where('tenant_id', $tenant->id)->count(),
                'available' => FleetVehicle::where('tenant_id', $tenant->id)->where('status', 'available')->count(),
                'active_rentals' => FleetBooking::where('tenant_id', $tenant->id)->where('status', 'active')->count(),
                'total_billed' => (float) FleetBooking::where('tenant_id', $tenant->id)->sum('total_billed'),
            ];
        }

        // Garage
        if ($modules->isModuleAccessible($tenant, 'garage') && (! $user || $user->hasPermission('garage.view'))) {
            $metrics['garage'] = [
                'total_jobs' => GarageJobCard::where('tenant_id', $tenant->id)->count(),
                'in_progress' => GarageJobCard::where('tenant_id', $tenant->id)->where('status', 'in_progress')->count(),
                'completed' => GarageJobCard::where('tenant_id', $tenant->id)->where('status', 'completed')->count(),
            ];
        }

        // Agrovet
        if ($modules->isModuleAccessible($tenant, 'agrovet') && (! $user || $user->hasPermission('agrovet.view'))) {
            $metrics['agrovet'] = [
                'total_customers' => AgrovetCustomer::where('tenant_id', $tenant->id)->count(),
                'outstanding_credit' => (float) AgrovetCustomer::where('tenant_id', $tenant->id)->sum('credit_balance'),
                'total_sales' => (float) AgrovetSale::where('tenant_id', $tenant->id)->sum('total_amount'),
            ];
        }

        return $metrics;
    }

    /**
     * Generate safe, sanitized CSV content for any requested export type.
     */
    public function exportData(Tenant $tenant, string $type, ?string $fromDate = null, ?string $toDate = null, ?int $shopId = null): string
    {
        $rows = [];
        $modules = $this->getModuleService();

        switch ($type) {
            case 'sales':
                $query = Sale::query()
                    ->where('tenant_id', $tenant->id)
                    ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
                    ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                    ->with(['shop', 'register', 'user', 'payments'])
                    ->orderByDesc('created_at');

                $rows[] = ['Sale ID', 'Sale Number', 'Date & Time', 'Shop', 'Register', 'Cashier', 'Subtotal (KES)', '16% Tax (KES)', 'Discount (KES)', 'Total Amount (KES)', 'Payment Methods', 'Status'];

                foreach ($query->get() as $s) {
                    $paymentMethods = $s->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->join(', ');
                    $rows[] = [
                        $s->id,
                        $this->sanitizeCsvValue($s->sale_number),
                        $s->created_at?->format('Y-m-d H:i:s'),
                        $this->sanitizeCsvValue($s->shop?->name),
                        $this->sanitizeCsvValue($s->register?->name ?? 'Default'),
                        $this->sanitizeCsvValue($s->user?->name),
                        number_format($s->subtotal, 2, '.', ''),
                        number_format($s->tax_total, 2, '.', ''),
                        number_format($s->discount_total, 2, '.', ''),
                        number_format($s->grand_total, 2, '.', ''),
                        $this->sanitizeCsvValue($paymentMethods),
                        $s->status,
                    ];
                }
                break;

            case 'products':
                $query = Product::query()
                    ->where('tenant_id', $tenant->id)
                    ->with('category')
                    ->orderBy('name');

                $rows[] = ['Product ID', 'SKU', 'Barcode', 'Name', 'Category', 'Base Unit', 'Cost Price (KES)', 'Selling Price (KES)', 'Is Active'];

                foreach ($query->get() as $p) {
                    $rows[] = [
                        $p->id,
                        $this->sanitizeCsvValue($p->sku ?? '—'),
                        $this->sanitizeCsvValue($p->barcode ?? '—'),
                        $this->sanitizeCsvValue($p->name),
                        $this->sanitizeCsvValue($p->category?->name ?? 'Uncategorized'),
                        $this->sanitizeCsvValue($p->unit ?? 'piece'),
                        number_format($p->cost_price ?? 0, 2, '.', ''),
                        number_format($p->selling_price ?? 0, 2, '.', ''),
                        $p->is_active ? 'Active' : 'Inactive',
                    ];
                }
                break;

            case 'shifts':
                $query = CashierShift::query()
                    ->where('tenant_id', $tenant->id)
                    ->when($fromDate, fn ($q) => $q->whereDate('opened_at', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('opened_at', '<=', $toDate))
                    ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                    ->with(['user', 'shop'])
                    ->orderByDesc('opened_at');

                $rows[] = ['Shift #', 'Cashier', 'Shop', 'Status', 'Opened At', 'Closed At', 'Opening Float (KES)', 'Cash Sales (KES)', 'Expected Cash (KES)', 'Actual Cash (KES)', 'Variance (KES)'];

                foreach ($query->get() as $sh) {
                    $rows[] = [
                        $this->sanitizeCsvValue($sh->shift_number),
                        $this->sanitizeCsvValue($sh->user?->name),
                        $this->sanitizeCsvValue($sh->shop?->name),
                        ucfirst($sh->status),
                        $sh->opened_at?->format('Y-m-d H:i:s'),
                        $sh->closed_at?->format('Y-m-d H:i:s') ?? 'Still Open',
                        number_format($sh->opening_float, 2, '.', ''),
                        number_format($sh->cash_sales, 2, '.', ''),
                        number_format($sh->expected_cash, 2, '.', ''),
                        number_format($sh->actual_cash ?? 0, 2, '.', ''),
                        number_format($sh->variance ?? 0, 2, '.', ''),
                    ];
                }
                break;

            case 'inventory':
                $query = InventoryStock::query()
                    ->where('tenant_id', $tenant->id)
                    ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                    ->with(['product', 'shop'])
                    ->orderBy('product_id');

                $rows[] = ['Product ID', 'SKU', 'Product Name', 'Shop Location', 'Quantity On Hand', 'Reorder Level', 'Selling Price (KES)', 'Total Valuation (KES)', 'Status'];

                foreach ($query->get() as $inv) {
                    $product = $inv->product;
                    $valuation = ($inv->quantity ?? 0) * ($product?->selling_price ?? 0);
                    $status = ($inv->quantity <= ($inv->reorder_level ?? 0)) ? 'Low Stock' : 'Adequate';

                    $rows[] = [
                        $inv->product_id,
                        $this->sanitizeCsvValue($product?->sku ?? '—'),
                        $this->sanitizeCsvValue($product?->name ?? 'Unknown'),
                        $this->sanitizeCsvValue($inv->shop?->name ?? 'Main'),
                        $inv->quantity,
                        $inv->reorder_level ?? 0,
                        number_format($product?->selling_price ?? 0, 2, '.', ''),
                        number_format($valuation, 2, '.', ''),
                        $status,
                    ];
                }
                break;

            case 'parcels':
                abort_unless($modules->isModuleAccessible($tenant, 'parcel-delivery'), 403, 'Unauthorized export for your industry.');
                $query = Parcel::query()
                    ->where('tenant_id', $tenant->id)
                    ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
                    ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
                    ->with(['originOffice', 'destinationOffice'])
                    ->orderByDesc('created_at');

                $rows[] = ['Tracking Number', 'Origin Office', 'Destination Office', 'Sender Name', 'Sender Phone', 'Recipient Name', 'Recipient Phone', 'Weight (KG)', 'Service Level', 'Fee (KES)', 'Status', 'Date Received'];

                foreach ($query->get() as $pr) {
                    $rows[] = [
                        $this->sanitizeCsvValue($pr->tracking_number),
                        $this->sanitizeCsvValue($pr->originOffice?->name),
                        $this->sanitizeCsvValue($pr->destinationOffice?->name),
                        $this->sanitizeCsvValue($pr->sender_name),
                        $this->sanitizeCsvValue($pr->sender_phone),
                        $this->sanitizeCsvValue($pr->recipient_name),
                        $this->sanitizeCsvValue($pr->recipient_phone),
                        $pr->weight_kg ?? '—',
                        ucfirst($pr->service_level),
                        number_format($pr->fee, 2, '.', ''),
                        ucfirst($pr->status),
                        $pr->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
                break;

            case 'gym':
                abort_unless($modules->isModuleAccessible($tenant, 'gym'), 403, 'Unauthorized export for your industry.');
                $query = GymSubscription::query()
                    ->where('tenant_id', $tenant->id)
                    ->with(['member', 'plan'])
                    ->orderByDesc('created_at');

                $rows[] = ['Member Name', 'Phone', 'Membership Plan', 'Start Date', 'End Date', 'Status', 'Session Credits Remaining'];

                foreach ($query->get() as $gs) {
                    $rows[] = [
                        $this->sanitizeCsvValue($gs->member?->name),
                        $this->sanitizeCsvValue($gs->member?->phone),
                        $this->sanitizeCsvValue($gs->plan?->name),
                        $gs->start_date,
                        $gs->end_date,
                        ucfirst($gs->status),
                        $gs->session_credits_remaining ?? 'Unlimited',
                    ];
                }
                break;

            case 'fleet':
                abort_unless($modules->isModuleAccessible($tenant, 'fleet'), 403, 'Unauthorized export for your industry.');
                $query = FleetBooking::query()
                    ->where('tenant_id', $tenant->id)
                    ->with(['vehicle', 'customer'])
                    ->orderByDesc('pickup_at');

                $rows[] = ['Booking ID', 'Vehicle Plate', 'Make & Model', 'Customer Name', 'Phone', 'Pickup Date', 'Return Date', 'Rate Type', 'Agreed Rate (KES)', 'Deposit Amount (KES)', 'Deposit Paid', 'Total Billed (KES)', 'Status'];

                foreach ($query->get() as $fb) {
                    $rows[] = [
                        $fb->id,
                        $this->sanitizeCsvValue($fb->vehicle?->plate),
                        $this->sanitizeCsvValue(($fb->vehicle?->make ?? '').' '.($fb->vehicle?->model ?? '')),
                        $this->sanitizeCsvValue($fb->customer?->name),
                        $this->sanitizeCsvValue($fb->customer?->phone),
                        $fb->pickup_at,
                        $fb->return_at,
                        ucfirst($fb->rate_type),
                        number_format($fb->agreed_rate, 2, '.', ''),
                        number_format($fb->deposit_amount, 2, '.', ''),
                        $fb->deposit_paid ? 'Yes' : 'No',
                        number_format($fb->total_billed, 2, '.', ''),
                        ucfirst($fb->status),
                    ];
                }
                break;

            case 'garage':
                abort_unless($modules->isModuleAccessible($tenant, 'garage'), 403, 'Unauthorized export for your industry.');
                $query = GarageJobCard::query()
                    ->where('tenant_id', $tenant->id)
                    ->with(['vehicle', 'customer', 'estimate'])
                    ->orderByDesc('created_at');

                $rows[] = ['Job Number', 'Vehicle Plate', 'Customer Name', 'Status', 'Mileage In', 'Estimate Parts (KES)', 'Estimate Labour (KES)', 'Grand Total (KES)', 'Received At'];

                foreach ($query->get() as $gj) {
                    $rows[] = [
                        $this->sanitizeCsvValue($gj->job_number),
                        $this->sanitizeCsvValue($gj->vehicle?->plate),
                        $this->sanitizeCsvValue($gj->customer?->name),
                        ucfirst($gj->status),
                        $gj->mileage_in ?? '—',
                        number_format($gj->estimate?->total_parts ?? 0, 2, '.', ''),
                        number_format($gj->estimate?->total_labour ?? 0, 2, '.', ''),
                        number_format($gj->estimate?->grand_total ?? 0, 2, '.', ''),
                        $gj->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
                break;
        }

        return $this->arrayToCsv($rows);
    }

    /**
     * Prevent CSV formula injection by prepending apostrophe if field starts with formula character.
     */
    public function sanitizeCsvValue(mixed $value): string
    {
        $str = (string) $value;
        if (strlen($str) > 0 && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"])) {
            return "'".$str;
        }

        return $str;
    }

    private function arrayToCsv(array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv ?: '';
    }

    private function resolveStartDate(string $period): ?Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '12m' => now()->subMonths(12),
            default => now()->subDays(30),
        };
    }
}
