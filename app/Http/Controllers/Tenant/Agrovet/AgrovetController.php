<?php

namespace App\Http\Controllers\Tenant\Agrovet;

use App\Http\Controllers\Controller;
use App\Models\AgrovetCreditLedger;
use App\Models\AgrovetCustomer;
use App\Models\AgrovetProduct;
use App\Models\AgrovetPurchase;
use App\Models\AgrovetSale;
use App\Models\AgrovetStockMovement;
use App\Models\AgrovetSupplier;
use App\Services\ModulePaymentService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgrovetController extends Controller
{
    public function __construct(private readonly ModulePaymentService $payments) {}

    public function index(): View
    {
        return view('tenant.agrovet.index', [
            'products' => AgrovetProduct::where('is_active', true)->count(),
            'lowStock' => AgrovetProduct::whereRaw('reorder_level > 0')->count(),
            'creditCustomers' => AgrovetCustomer::where('credit_balance', '>', 0)->count(),
            'todaySales' => AgrovetSale::whereDate('sold_at', today())->count(),
        ]);
    }

    public function products(): View
    {
        return view('tenant.agrovet.products', ['products' => AgrovetProduct::withoutTrashed()->paginate(20)]);
    }

    public function createProduct(): View
    {
        return view('tenant.agrovet.products-create');
    }

    public function storeProduct(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'unit_base' => 'required|string',
            'unit_alt' => 'nullable|string',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'grade' => 'nullable|string',
            'reorder_level' => 'required|numeric|min:0',
        ]);
        AgrovetProduct::create($data);

        return redirect()->route('tenant.agrovet.products')->with('success', 'Product added.');
    }

    public function customers(): View
    {
        return view('tenant.agrovet.customers', ['customers' => AgrovetCustomer::withoutTrashed()->paginate(20)]);
    }

    public function createCustomer(): View
    {
        return view('tenant.agrovet.customers-create');
    }

    public function storeCustomer(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'village' => 'nullable|string',
            'credit_limit' => 'required|numeric|min:0',
        ]);
        AgrovetCustomer::create($data);

        return redirect()->route('tenant.agrovet.customers')->with('success', 'Customer added.');
    }

    public function purchases(): View
    {
        return view('tenant.agrovet.purchases', ['purchases' => AgrovetPurchase::with(['supplier', 'product'])->paginate(20)]);
    }

    public function createPurchase(): View
    {
        return view('tenant.agrovet.purchases-create', [
            'products' => AgrovetProduct::where('is_active', true)->get(),
            'suppliers' => AgrovetSupplier::withoutTrashed()->get(),
        ]);
    }

    public function storePurchase(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'supplier_id' => 'nullable|integer',
            'product_id' => 'required|integer',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
        ]);
        $data['total_cost'] = $data['quantity'] * $data['unit_cost'];
        $data['purchased_at'] = now();
        $purch = AgrovetPurchase::create($data);

        AgrovetStockMovement::create([
            'product_id' => $data['product_id'],
            'batch_number' => $data['batch_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'movement_type' => 'purchase',
            'quantity' => $data['quantity'],
            'unit' => AgrovetProduct::find($data['product_id'])->unit_base,
            'unit_price' => $data['unit_cost'],
            'reference_id' => $purch->id,
            'reference_type' => 'purchase',
            'moved_at' => now(),
        ]);

        return redirect()->route('tenant.agrovet.purchases')->with('success', 'Purchase recorded.');
    }

    public function sales(): View
    {
        return view('tenant.agrovet.sales', ['sales' => AgrovetSale::with('customer')->paginate(20)]);
    }

    public function createSale(): View
    {
        return view('tenant.agrovet.sales-create', [
            'products' => AgrovetProduct::where('is_active', true)->get(),
            'customers' => AgrovetCustomer::where('is_active', true)->get(),
        ]);
    }

    public function storeSale(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'customer_id' => 'nullable|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.001',
            'payment_type' => 'required|in:cash,credit,mpesa',
            'reference' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
        ]);
        $product = AgrovetProduct::findOrFail($data['product_id']);
        $total = $data['quantity'] * $data['unit_price'];
        $tenant = TenantContext::getTenant();
        $payMeta = null;
        if ($data['payment_type'] !== 'credit') {
            $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['payment_type'] === 'mpesa' ? 'mpesa' : 'cash', $total, 'agrovet', $data['reference'] ?? null);
        }

        if ($data['payment_type'] === 'credit' && ! empty($data['customer_id'])) {
            $customer = AgrovetCustomer::findOrFail($data['customer_id']);
            abort_if($customer->credit_balance + $total > $customer->credit_limit, 422, 'Credit limit exceeded.');
        }

        $saleNumber = 'AV-'.date('Ymd').'-'.strtoupper(substr(uniqid(), 0, 5));
        $sale = AgrovetSale::create([
            'customer_id' => $data['customer_id'] ?? null,
            'sale_number' => $saleNumber,
            'total_amount' => $total,
            'payment_type' => $data['payment_type'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'sold_at' => now(),
        ]);

        AgrovetStockMovement::create([
            'product_id' => $data['product_id'],
            'movement_type' => 'sale',
            'quantity' => -abs($data['quantity']),
            'unit' => $product->unit_base,
            'unit_price' => $data['unit_price'],
            'reference_id' => $sale->id,
            'reference_type' => 'sale',
            'moved_at' => now(),
        ]);

        if ($data['payment_type'] === 'credit' && ! empty($data['customer_id'])) {
            AgrovetCreditLedger::create([
                'customer_id' => $data['customer_id'],
                'type' => 'sale',
                'amount' => $total,
                'reference' => $saleNumber,
            ]);
            AgrovetCustomer::where('id', $data['customer_id'])->increment('credit_balance', $total);
        }

        return redirect()->route('tenant.agrovet.sales')->with('success', 'Sale recorded.');
    }

    public function repayCredit(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'customer_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
        ]);
        $customer = AgrovetCustomer::findOrFail($data['customer_id']);
        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['method'], $data['amount'], 'agrovet', $data['reference'] ?? null);

        AgrovetCreditLedger::create([
            'customer_id' => $customer->id,
            'type' => 'repayment',
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
            'method' => $data['method'],
            'payment_meta' => $payMeta,
        ]);
        $customer->decrement('credit_balance', min($data['amount'], $customer->credit_balance));

        return back()->with('success', 'Repayment recorded.');
    }

    public function creditLedger(AgrovetCustomer $customer): View
    {
        $ledger = AgrovetCreditLedger::where('customer_id', $customer->id)->orderByDesc('created_at')->paginate(30);
        $aging = $this->computeAging($customer);

        return view('tenant.agrovet.credit-ledger', compact('customer', 'ledger', 'aging'));
    }

    private function computeAging(AgrovetCustomer $customer): array
    {
        $entries = AgrovetCreditLedger::where('customer_id', $customer->id)->where('type', 'sale')->get();
        $buckets = ['0_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        foreach ($entries as $e) {
            $days = Carbon::parse($e->created_at)->diffInDays(now());
            if ($days <= 30) {
                $buckets['0_30'] += $e->amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $e->amount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $e->amount;
            } else {
                $buckets['over_90'] += $e->amount;
            }
        }

        return $buckets;
    }
}
