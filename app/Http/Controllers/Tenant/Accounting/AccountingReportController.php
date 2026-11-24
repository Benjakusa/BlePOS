<?php

namespace App\Http\Controllers\Tenant\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Services\AccountingService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingReportController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function trialBalance(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $this->accountingService->seedChartOfAccounts($tenant);
        $asOfDate = $request->string('as_of_date', now()->toDateString())->value();

        $report = $this->accountingService->getTrialBalance($tenant, $asOfDate);

        return view('tenant.accounting.reports.trial_balance', [
            'tenant' => $tenant,
            'report' => $report,
            'asOfDate' => $asOfDate,
        ]);
    }

    public function profitLoss(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $this->accountingService->seedChartOfAccounts($tenant);
        $startDate = $request->string('start_date', now()->startOfMonth()->toDateString())->value();
        $endDate = $request->string('end_date', now()->toDateString())->value();

        $report = $this->accountingService->getProfitAndLoss($tenant, $startDate, $endDate);

        return view('tenant.accounting.reports.profit_loss', [
            'tenant' => $tenant,
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function balanceSheet(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $this->accountingService->seedChartOfAccounts($tenant);
        $asOfDate = $request->string('as_of_date', now()->toDateString())->value();

        $report = $this->accountingService->getBalanceSheet($tenant, $asOfDate);

        return view('tenant.accounting.reports.balance_sheet', [
            'tenant' => $tenant,
            'report' => $report,
            'asOfDate' => $asOfDate,
        ]);
    }

    public function generalLedger(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $this->accountingService->seedChartOfAccounts($tenant);
        $accounts = Account::query()->where('tenant_id', $tenant->id)->orderBy('code')->get();

        $selectedAccountId = $request->integer('account_id', $accounts->first()?->id ?? 0);
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

        $lines = collect();
        if ($selectedAccount) {
            $lines = JournalEntryLine::query()
                ->where('account_id', $selectedAccount->id)
                ->whereHas('journalEntry', function ($q): void {
                    $q->where('status', 'posted');
                })
                ->with(['journalEntry'])
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.entry_date')
                ->orderBy('journal_entries.id')
                ->select('journal_entry_lines.*')
                ->get();
        }

        return view('tenant.accounting.reports.general_ledger', [
            'tenant' => $tenant,
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'lines' => $lines,
        ]);
    }
}
