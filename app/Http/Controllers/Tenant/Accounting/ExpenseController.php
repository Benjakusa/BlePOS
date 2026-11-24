<?php

namespace App\Http\Controllers\Tenant\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Expense;
use App\Services\AccountingService;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $query = Expense::query()
            ->where('tenant_id', $tenant->id)
            ->with(['account', 'paymentAccount', 'shop', 'user'])
            ->latest('expense_date')
            ->latest('id');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->value());
        }

        $expenses = $query->paginate(25);

        return view('tenant.accounting.expenses.index', [
            'tenant' => $tenant,
            'expenses' => $expenses,
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $this->accountingService->seedChartOfAccounts($tenant);

        $expenseAccounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $paymentAccounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('tenant.accounting.expenses.create', [
            'tenant' => $tenant,
            'expenseAccounts' => $expenseAccounts,
            'paymentAccounts' => $paymentAccounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $shop = ShopContext::hasShop() ? ShopContext::getShop() : null;
        abort_if($shop === null, 422, 'Please select a shop/branch before recording expenses.');

        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:64'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'in:cash,mpesa,bank,card'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'payment_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'receipt_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $expense = $this->accountingService->recordExpense(
            shop: $shop,
            user: $request->user(),
            data: $validated
        );

        return redirect()->route('tenant.accounting.expenses.index')
            ->with('success', "Expense #{$expense->expense_number} recorded and posted to ledger.");
    }
}
