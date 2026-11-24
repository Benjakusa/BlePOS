<?php

namespace App\Http\Controllers\Tenant\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountsController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        // Ensure baseline accounts exist
        $this->accountingService->seedChartOfAccounts($tenant);

        $query = Account::query()->where('tenant_id', $tenant->id);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->trim()->value();
            $query->where(function ($sq) use ($q): void {
                $sq->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }

        $accounts = $query->orderBy('code')->get();

        return view('tenant.accounting.accounts.index', [
            'tenant' => $tenant,
            'accounts' => $accounts,
            'type' => $request->string('type')->value(),
            'q' => $request->string('q')->value(),
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        return view('tenant.accounting.accounts.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:accounts,code,NULL,id,tenant_id,'.$tenant->id],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['nullable', 'string', 'max:40'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Account::query()->create([
            'tenant_id' => $tenant->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'sub_type' => $validated['sub_type'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_system' => false,
            'is_active' => true,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('tenant.accounting.accounts.index')
            ->with('success', "Account [{$validated['code']}] {$validated['name']} created successfully.");
    }
}
