<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\InventoryStock;
use App\Models\PlatformInvoice;
use App\Models\Register;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shop;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $user = $request->user();

        // 1. Determine role view perspective
        $isOwner = $user->hasRole('owner') || $user->hasPermission('shops.all.manage') || strtolower($user->email) === strtolower($tenant->owner_email);
        $isManager = $user->hasRole('shop-manager') || $user->hasPermission('inventory.manage');
        $isCashier = $user->hasRole('cashier');

        $requestedView = $request->query('view');
        if ($requestedView === 'cashier' && ($isCashier || $isOwner || $isManager)) {
            $roleView = 'cashier';
        } elseif ($requestedView === 'manager' && ($isManager || $isOwner)) {
            $roleView = 'shop-manager';
        } elseif ($requestedView === 'owner' && $isOwner) {
            $roleView = 'owner';
        } elseif ($isCashier && ! $isManager && ! $isOwner) {
            $roleView = 'cashier';
        } elseif ($isManager && ! $isOwner) {
            $roleView = 'shop-manager';
        } else {
            $roleView = 'owner';
        }

        // 2. Determine accessible shops and active shop
        $accessibleShops = $isOwner
            ? Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get()
            : $user->shops()->where('shops.tenant_id', $tenant->id)->orderBy('name')->get();

        $activeShop = ShopContext::getShop();
        if ($activeShop === null && ($roleView === 'cashier' || $roleView === 'shop-manager')) {
            $activeShop = $accessibleShops->first();
            if ($activeShop) {
                ShopContext::setShop($activeShop);
            }
        }

        // 3. Date filtering
        $period = $request->query('period', 'today');
        $startDate = match ($period) {
            '7d' => Carbon::now()->subDays(7)->startOfDay(),
            '30d' => Carbon::now()->subDays(30)->startOfDay(),
            default => Carbon::now()->startOfDay(),
        };

        // 4. Role-specific aggregates
        $data = [
            'tenant' => $tenant,
            'user' => $user,
            'roleView' => $roleView,
            'isOwner' => $isOwner,
            'isManager' => $isManager,
            'isCashier' => $isCashier,
            'period' => $period,
            'accessibleShops' => $accessibleShops,
            'activeShop' => $activeShop,
        ];

        if ($roleView === 'cashier') {
            // Cashier Perspective: Scoped strictly to cashier user and active shop
            $currentShift = CashierShift::query()
                ->with(['register'])
                ->where('user_id', $user->id)
                ->where('status', 'open')
                ->when($activeShop, fn ($q) => $q->where('shop_id', $activeShop->id))
                ->first();

            $shiftTransactionsCount = 0;
            $recentSales = collect();

            if ($currentShift) {
                $shiftTransactionsCount = Sale::query()
                    ->where('cashier_shift_id', $currentShift->id)
                    ->where('status', 'completed')
                    ->count();

                $recentSales = Sale::query()
                    ->where('cashier_shift_id', $currentShift->id)
                    ->latest('id')
                    ->limit(5)
                    ->get();
            }

            $availableRegisters = $activeShop
                ? Register::query()->where('shop_id', $activeShop->id)->where('is_active', true)->get()
                : collect();

            $data['cashierData'] = [
                'currentShift' => $currentShift,
                'shiftTransactionsCount' => $shiftTransactionsCount,
                'recentSales' => $recentSales,
                'availableRegisters' => $availableRegisters,
            ];
        } elseif ($roleView === 'shop-manager') {
            // Shop Manager Perspective: Scoped to active shop
            $shopId = $activeShop?->id;

            $salesCount = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->count();

            $salesTotal = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->sum('grand_total');

            $averageBasket = $salesCount > 0
                ? bcdiv((string) $salesTotal, (string) $salesCount, 2)
                : '0.00';

            $cashSales = SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                    ->when($shopId, fn ($sq) => $sq->where('shop_id', $shopId))
                    ->where('created_at', '>=', $startDate)
                )
                ->where('payment_method', 'cash')
                ->sum('amount');

            $mpesaSales = SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                    ->when($shopId, fn ($sq) => $sq->where('shop_id', $shopId))
                    ->where('created_at', '>=', $startDate)
                )
                ->where('payment_method', 'mpesa')
                ->sum('amount');

            $openShifts = CashierShift::query()
                ->with(['user', 'register'])
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('status', 'open')
                ->get();

            $shiftVariances = CashierShift::query()
                ->with(['user', 'register'])
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('status', 'closed')
                ->where('closed_at', '>=', $startDate)
                ->where('variance', '!=', 0)
                ->get();

            $lowStockItems = InventoryStock::query()
                ->with('product')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('quantity', '<=', 5)
                ->limit(10)
                ->get();

            $outOfStockCount = InventoryStock::query()
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('quantity', '<=', 0)
                ->count();

            $data['managerData'] = [
                'salesCount' => $salesCount,
                'salesTotal' => $salesTotal,
                'averageBasket' => $averageBasket,
                'cashSales' => $cashSales,
                'mpesaSales' => $mpesaSales,
                'openShifts' => $openShifts,
                'shiftVariances' => $shiftVariances,
                'lowStockItems' => $lowStockItems,
                'outOfStockCount' => $outOfStockCount,
            ];
        } else {
            // Owner / Business Manager Perspective: Full tenant overview or scoped by active shop
            $shopId = $activeShop?->id;

            $salesCount = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->count();

            $grossSales = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->sum('grand_total');

            $taxTotal = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->sum('tax_total');

            $netSales = Sale::query()
                ->where('status', 'completed')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->sum('subtotal');

            $averageBasket = $salesCount > 0
                ? bcdiv((string) $grossSales, (string) $salesCount, 2)
                : '0.00';

            $cashSales = SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                    ->when($shopId, fn ($sq) => $sq->where('shop_id', $shopId))
                    ->where('created_at', '>=', $startDate)
                )
                ->where('payment_method', 'cash')
                ->sum('amount');

            $mpesaSales = SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                    ->when($shopId, fn ($sq) => $sq->where('shop_id', $shopId))
                    ->where('created_at', '>=', $startDate)
                )
                ->where('payment_method', 'mpesa')
                ->sum('amount');

            $voidedSalesCount = Sale::query()
                ->where('status', 'voided')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->count();

            $voidedSalesTotal = Sale::query()
                ->where('status', 'voided')
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('created_at', '>=', $startDate)
                ->sum('grand_total');

            // Multi-shop performance summary
            $shopPerformance = $accessibleShops->map(function ($shop) use ($startDate) {
                $count = Sale::query()
                    ->where('status', 'completed')
                    ->where('shop_id', $shop->id)
                    ->where('created_at', '>=', $startDate)
                    ->count();

                $total = Sale::query()
                    ->where('status', 'completed')
                    ->where('shop_id', $shop->id)
                    ->where('created_at', '>=', $startDate)
                    ->sum('grand_total');

                $openShifts = CashierShift::query()
                    ->where('shop_id', $shop->id)
                    ->where('status', 'open')
                    ->count();

                return [
                    'shop' => $shop,
                    'sales_count' => $count,
                    'sales_total' => $total,
                    'open_shifts' => $openShifts,
                ];
            });

            // Top selling products
            $topProducts = SaleItem::query()
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                    ->when($shopId, fn ($sq) => $sq->where('shop_id', $shopId))
                    ->where('created_at', '>=', $startDate)
                )
                ->select('product_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_price) as total_revenue'))
                ->groupBy('product_name')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();

            $lowStockCount = InventoryStock::query()
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('quantity', '<=', 5)
                ->count();

            $outOfStockCount = InventoryStock::query()
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->where('quantity', '<=', 0)
                ->count();

            $activeSubscription = $tenant->subscriptions()->with('plan')->latest('id')->first();
            $openInvoices = PlatformInvoice::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
                ->where('amount_due', '>', 0)
                ->get();

            $data['ownerData'] = [
                'salesCount' => $salesCount,
                'grossSales' => $grossSales,
                'taxTotal' => $taxTotal,
                'netSales' => $netSales,
                'averageBasket' => $averageBasket,
                'cashSales' => $cashSales,
                'mpesaSales' => $mpesaSales,
                'voidedSalesCount' => $voidedSalesCount,
                'voidedSalesTotal' => $voidedSalesTotal,
                'shopPerformance' => $shopPerformance,
                'topProducts' => $topProducts,
                'lowStockCount' => $lowStockCount,
                'outOfStockCount' => $outOfStockCount,
                'subscription' => $activeSubscription,
                'openInvoicesCount' => $openInvoices->count(),
                'openInvoicesDue' => $openInvoices->sum('amount_due'),
            ];
        }

        return view('tenant.dashboard', $data);
    }
}
