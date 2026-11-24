<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingService
{
    /**
     * Standard Kenyan Chart of Accounts definitions.
     *
     * @var array<int, array{code: string, name: string, type: string, sub_type: string, description: string}>
     */
    public const STANDARD_CHART_OF_ACCOUNTS = [
        ['code' => '1010', 'name' => 'Cash on Hand (Till / Drawer)', 'type' => 'asset', 'sub_type' => 'current_asset', 'description' => 'Physical cash float and cashier registers'],
        ['code' => '1020', 'name' => 'M-Pesa Clearing Account', 'type' => 'asset', 'sub_type' => 'current_asset', 'description' => 'Direct Safaricom Daraja digital collections'],
        ['code' => '1030', 'name' => 'Bank & Card Clearing Account', 'type' => 'asset', 'sub_type' => 'current_asset', 'description' => 'Commercial bank settlement and card terminal funds'],
        ['code' => '1040', 'name' => 'Accounts Receivable (Debtors)', 'type' => 'asset', 'sub_type' => 'current_asset', 'description' => 'Customer credit balances and unpaid invoices'],
        ['code' => '1050', 'name' => 'Inventory Asset', 'type' => 'asset', 'sub_type' => 'current_asset', 'description' => 'Merchandise stock valuation at weighted average cost'],
        ['code' => '2010', 'name' => 'Accounts Payable (Creditors)', 'type' => 'liability', 'sub_type' => 'current_liability', 'description' => 'Vendor bills and supplier trade liabilities'],
        ['code' => '2020', 'name' => 'VAT Payable (16% KRA Output Tax)', 'type' => 'liability', 'sub_type' => 'current_liability', 'description' => 'Value Added Tax collected on taxable sales'],
        ['code' => '3010', 'name' => "Owner's Equity & Retained Earnings", 'type' => 'equity', 'sub_type' => 'equity', 'description' => 'Capital invested and accumulated business profits'],
        ['code' => '4010', 'name' => 'Merchandise Sales Revenue', 'type' => 'revenue', 'sub_type' => 'operating_revenue', 'description' => 'Gross income from POS retail transactions'],
        ['code' => '4020', 'name' => 'Sales Discounts Allowed', 'type' => 'revenue', 'sub_type' => 'contra_revenue', 'description' => 'Promotional price markdowns and cashier discounts'],
        ['code' => '5010', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'sub_type' => 'cogs', 'description' => 'Acquisition cost of products sold to customers'],
        ['code' => '5020', 'name' => 'Store Operating Expenses', 'type' => 'expense', 'sub_type' => 'operating_expense', 'description' => 'General shop operational expenditures and utilities'],
        ['code' => '5030', 'name' => 'Cash Reconciliation Variance', 'type' => 'expense', 'sub_type' => 'operating_expense', 'description' => 'Cash register shift shortages or overages'],
    ];

    /**
     * Seed or verify the standard Chart of Accounts for a tenant.
     */
    public function seedChartOfAccounts(Tenant $tenant): void
    {
        foreach (self::STANDARD_CHART_OF_ACCOUNTS as $accountData) {
            Account::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $accountData['code'],
                ],
                [
                    'name' => $accountData['name'],
                    'type' => $accountData['type'],
                    'sub_type' => $accountData['sub_type'],
                    'currency' => 'KES',
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_system' => true,
                    'is_active' => true,
                    'description' => $accountData['description'],
                ]
            );
        }
    }

    /**
     * Record a balanced double-entry journal entry.
     *
     * @param  array<int, array{account_id: int, debit: numeric|string, credit: numeric|string, memo?: ?string}>  $lines
     */
    public function recordJournalEntry(
        Tenant $tenant,
        array $lines,
        string $description,
        ?Shop $shop = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?User $user = null,
        ?string $entryDate = null
    ): JournalEntry {
        if (count($lines) < 2) {
            throw new InvalidArgumentException('A journal entry must contain at least two lines.');
        }

        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($lines as $line) {
            $debit = (string) ($line['debit'] ?? '0.00');
            $credit = (string) ($line['credit'] ?? '0.00');

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        // Invariant: Debits must strictly equal Credits
        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw new InvalidArgumentException(
                "Unbalanced journal entry: Total debits ({$totalDebit}) do not equal total credits ({$totalCredit})."
            );
        }

        if (bccomp($totalDebit, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('Journal entry amounts must be greater than zero.');
        }

        return DB::transaction(function () use (
            $tenant,
            $lines,
            $description,
            $shop,
            $referenceType,
            $referenceId,
            $user,
            $entryDate,
            $totalDebit,
            $totalCredit
        ): JournalEntry {
            $entryNumber = 'JE-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            $journalEntry = JournalEntry::query()->create([
                'tenant_id' => $tenant->id,
                'shop_id' => $shop?->id,
                'created_by_user_id' => $user?->id,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate ?? now()->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'status' => 'posted',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            foreach ($lines as $lineData) {
                $account = Account::query()
                    ->where('tenant_id', $tenant->id)
                    ->findOrFail($lineData['account_id']);

                $debit = (string) ($lineData['debit'] ?? '0.00');
                $credit = (string) ($lineData['credit'] ?? '0.00');

                JournalEntryLine::query()->create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $lineData['memo'] ?? null,
                ]);

                // Update account balance according to normal balance rules
                if ($account->isDebitNormal()) {
                    // Assets & Expenses: Balance = Balance + Debit - Credit
                    $netChange = bcsub($debit, $credit, 2);
                    $newBalance = bcadd((string) $account->current_balance, $netChange, 2);
                } else {
                    // Liabilities, Equity & Revenue: Balance = Balance + Credit - Debit
                    $netChange = bcsub($credit, $debit, 2);
                    $newBalance = bcadd((string) $account->current_balance, $netChange, 2);
                }

                $account->update(['current_balance' => $newBalance]);
            }

            return $journalEntry;
        });
    }

    /**
     * Automatically generate double-entry journal entries for a completed POS sale.
     */
    public function recordSaleJournal(Sale $sale): JournalEntry
    {
        $tenant = $sale->tenant;
        $this->seedChartOfAccounts($tenant);

        // Fetch accounts
        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('code', ['1010', '1020', '1030', '1050', '2020', '4010', '5010'])
            ->get()
            ->keyBy('code');

        $lines = [];

        // 1. Debits: Payment accounts (Cash, M-Pesa, Bank/Card)
        $changeRemaining = (string) ($sale->change_due ?? '0.00');

        foreach ($sale->payments as $payment) {
            $code = match ($payment->payment_method) {
                'mpesa' => '1020',
                'card', 'bank', 'cheque' => '1030',
                default => '1010',
            };

            $account = $accounts->get($code) ?? $accounts->get('1010');
            $payAmount = (string) $payment->amount;

            // If cash and change was returned, debit net cash received into till
            if ($payment->payment_method === 'cash' && bccomp($changeRemaining, '0.00', 2) > 0) {
                if (bccomp($payAmount, $changeRemaining, 2) >= 0) {
                    $payAmount = bcsub($payAmount, $changeRemaining, 2);
                    $changeRemaining = '0.00';
                } else {
                    $changeRemaining = bcsub($changeRemaining, $payAmount, 2);
                    $payAmount = '0.00';
                }
            }

            if (bccomp($payAmount, '0.00', 2) > 0) {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $payAmount,
                    'credit' => '0.00',
                    'memo' => "POS Sale #{$sale->sale_number} ({$payment->payment_method})",
                ];
            }
        }

        // 2. Credits: Revenue & VAT
        // Net Revenue = Grand Total - Tax Total
        $netRevenue = bcsub((string) $sale->grand_total, (string) $sale->tax_total, 2);

        if (bccomp($netRevenue, '0.00', 2) > 0) {
            $revAccount = $accounts->get('4010');
            $lines[] = [
                'account_id' => $revAccount->id,
                'debit' => '0.00',
                'credit' => $netRevenue,
                'memo' => "Merchandise Sales Revenue (#{$sale->sale_number})",
            ];
        }

        if (bccomp((string) $sale->tax_total, '0.00', 2) > 0) {
            $vatAccount = $accounts->get('2020');
            $lines[] = [
                'account_id' => $vatAccount->id,
                'debit' => '0.00',
                'credit' => (string) $sale->tax_total,
                'memo' => "KRA 16% VAT Output Tax (#{$sale->sale_number})",
            ];
        }

        // 3. Inventory & COGS Entry (if products track stock and cost price > 0)
        $totalCost = '0.00';
        foreach ($sale->items as $item) {
            if ($item->product && $item->product->track_stock && (float) $item->unit_cost > 0) {
                $lineCost = bcmul((string) $item->unit_cost, (string) $item->quantity, 2);
                $totalCost = bcadd($totalCost, $lineCost, 2);
            }
        }

        if (bccomp($totalCost, '0.00', 2) > 0) {
            $cogsAccount = $accounts->get('5010');
            $invAccount = $accounts->get('1050');

            $lines[] = [
                'account_id' => $cogsAccount->id,
                'debit' => $totalCost,
                'credit' => '0.00',
                'memo' => "COGS (#{$sale->sale_number})",
            ];

            $lines[] = [
                'account_id' => $invAccount->id,
                'debit' => '0.00',
                'credit' => $totalCost,
                'memo' => "Inventory Stock Deduction (#{$sale->sale_number})",
            ];
        }

        return $this->recordJournalEntry(
            tenant: $tenant,
            lines: $lines,
            description: "POS Sale #{$sale->sale_number}",
            shop: $sale->shop,
            referenceType: 'Sale',
            referenceId: (string) $sale->id,
            user: $sale->user,
            entryDate: $sale->created_at?->toDateString() ?? now()->toDateString()
        );
    }

    /**
     * Record a reversing journal entry when a sale is voided.
     */
    public function voidSaleJournal(Sale $sale, User $user, string $reason): ?JournalEntry
    {
        $originalEntry = JournalEntry::query()
            ->where('tenant_id', $sale->tenant_id)
            ->where('reference_type', 'Sale')
            ->where('reference_id', (string) $sale->id)
            ->where('status', 'posted')
            ->with('lines')
            ->first();

        if (! $originalEntry) {
            return null;
        }

        $reversingLines = [];
        foreach ($originalEntry->lines as $line) {
            $reversingLines[] = [
                'account_id' => $line->account_id,
                'debit' => (string) $line->credit, // Swap credit to debit
                'credit' => (string) $line->debit, // Swap debit to credit
                'memo' => "Void reversal for #{$sale->sale_number}: {$reason}",
            ];
        }

        $reversalEntry = $this->recordJournalEntry(
            tenant: $sale->tenant,
            lines: $reversingLines,
            description: "Reversal for Voided Sale #{$sale->sale_number}",
            shop: $sale->shop,
            referenceType: 'SaleVoid',
            referenceId: (string) $sale->id,
            user: $user,
            entryDate: now()->toDateString()
        );

        $originalEntry->update(['status' => 'voided']);

        return $reversalEntry;
    }

    /**
     * Record an operating expense with automatic double-entry posting.
     *
     * @param  array{category: string, amount: numeric|string, tax_amount?: numeric|string, payment_method?: string, description: string, expense_date?: string, account_id?: int, payment_account_id?: int, receipt_reference?: ?string}  $data
     */
    public function recordExpense(Shop $shop, User $user, array $data): Expense
    {
        $tenant = $shop->tenant;
        $this->seedChartOfAccounts($tenant);

        return DB::transaction(function () use ($shop, $user, $data, $tenant): Expense {
            $expenseAccount = isset($data['account_id'])
                ? Account::query()->where('tenant_id', $tenant->id)->findOrFail($data['account_id'])
                : Account::query()->where('tenant_id', $tenant->id)->where('code', '5020')->firstOrFail();

            $paymentCode = match ($data['payment_method'] ?? 'cash') {
                'mpesa' => '1020',
                'bank', 'card' => '1030',
                default => '1010',
            };

            $paymentAccount = isset($data['payment_account_id'])
                ? Account::query()->where('tenant_id', $tenant->id)->findOrFail($data['payment_account_id'])
                : Account::query()->where('tenant_id', $tenant->id)->where('code', $paymentCode)->firstOrFail();

            $amount = (string) $data['amount'];
            $expenseNumber = 'EXP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));

            $expense = Expense::query()->create([
                'tenant_id' => $tenant->id,
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'account_id' => $expenseAccount->id,
                'payment_account_id' => $paymentAccount->id,
                'expense_number' => $expenseNumber,
                'category' => $data['category'],
                'amount' => $amount,
                'tax_amount' => (string) ($data['tax_amount'] ?? '0.00'),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'description' => $data['description'],
                'receipt_reference' => $data['receipt_reference'] ?? null,
                'status' => 'approved',
            ]);

            // Post journal entry: Dr Expense Account, Cr Payment Account
            $journalEntry = $this->recordJournalEntry(
                tenant: $tenant,
                lines: [
                    [
                        'account_id' => $expenseAccount->id,
                        'debit' => $amount,
                        'credit' => '0.00',
                        'memo' => "Expense #{$expenseNumber}: {$data['description']}",
                    ],
                    [
                        'account_id' => $paymentAccount->id,
                        'debit' => '0.00',
                        'credit' => $amount,
                        'memo' => "Paid via {$expense->payment_method} (#{$expenseNumber})",
                    ],
                ],
                description: "Expense: {$data['description']}",
                shop: $shop,
                referenceType: 'Expense',
                referenceId: (string) $expense->id,
                user: $user,
                entryDate: $expense->expense_date->toDateString()
            );

            $expense->update(['journal_entry_id' => $journalEntry->id]);

            return $expense;
        });
    }

    /**
     * Record cash variance adjustment when closing a cashier shift.
     */
    public function recordCashierShiftReconciliation(CashierShift $shift): ?JournalEntry
    {
        if ($shift->variance === null || bccomp((string) $shift->variance, '0.00', 2) === 0) {
            return null;
        }

        $tenant = $shift->tenant;
        $this->seedChartOfAccounts($tenant);

        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('code', ['1010', '5030'])
            ->get()
            ->keyBy('code');

        $cashAccount = $accounts->get('1010');
        $varianceAccount = $accounts->get('5030');

        $variance = (string) $shift->variance;

        if (bccomp($variance, '0.00', 2) < 0) {
            // Cash shortage: Dr Cash Variance Expense, Cr Cash on Hand
            $shortageAmount = bcsub('0.00', $variance, 2);
            $lines = [
                [
                    'account_id' => $varianceAccount->id,
                    'debit' => $shortageAmount,
                    'credit' => '0.00',
                    'memo' => "Shift #{$shift->id} Cash Shortage",
                ],
                [
                    'account_id' => $cashAccount->id,
                    'debit' => '0.00',
                    'credit' => $shortageAmount,
                    'memo' => "Till deduction for shift variance (#{$shift->id})",
                ],
            ];
            $desc = "Cashier Shift #{$shift->id} Cash Shortage Adjustment";
        } else {
            // Cash overage: Dr Cash on Hand, Cr Cash Variance (reversal/income)
            $lines = [
                [
                    'account_id' => $cashAccount->id,
                    'debit' => $variance,
                    'credit' => '0.00',
                    'memo' => "Till overage addition for shift (#{$shift->id})",
                ],
                [
                    'account_id' => $varianceAccount->id,
                    'debit' => '0.00',
                    'credit' => $variance,
                    'memo' => "Shift #{$shift->id} Cash Overage",
                ],
            ];
            $desc = "Cashier Shift #{$shift->id} Cash Overage Adjustment";
        }

        return $this->recordJournalEntry(
            tenant: $tenant,
            lines: $lines,
            description: $desc,
            shop: $shift->shop,
            referenceType: 'CashierShift',
            referenceId: (string) $shift->id,
            user: $shift->user,
            entryDate: now()->toDateString()
        );
    }

    /**
     * Compute Trial Balance proving sum(debit) == sum(credit).
     *
     * @return array{accounts: Collection<int, array{code: string, name: string, type: string, debit: string, credit: string}>, total_debit: string, total_credit: string, is_balanced: bool}
     */
    public function getTrialBalance(Tenant $tenant, ?string $asOfDate = null): array
    {
        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = collect();
        $grandDebit = '0.00';
        $grandCredit = '0.00';

        foreach ($accounts as $account) {
            $linesQuery = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate): void {
                    $q->where('status', 'posted');
                    if ($asOfDate) {
                        $q->whereDate('entry_date', '<=', $asOfDate);
                    }
                });

            $totalDebit = (string) $linesQuery->sum('debit');
            $totalCredit = (string) $linesQuery->sum('credit');

            $netBalance = bcsub($totalDebit, $totalCredit, 2);

            $debitBalance = '0.00';
            $creditBalance = '0.00';

            if ($account->isDebitNormal()) {
                if (bccomp($netBalance, '0.00', 2) >= 0) {
                    $debitBalance = $netBalance;
                } else {
                    $creditBalance = bcsub('0.00', $netBalance, 2);
                }
            } else {
                $creditNet = bcsub($totalCredit, $totalDebit, 2);
                if (bccomp($creditNet, '0.00', 2) >= 0) {
                    $creditBalance = $creditNet;
                } else {
                    $debitBalance = bcsub('0.00', $creditNet, 2);
                }
            }

            if (bccomp($debitBalance, '0.00', 2) !== 0 || bccomp($creditBalance, '0.00', 2) !== 0) {
                $rows->push([
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => $debitBalance,
                    'credit' => $creditBalance,
                ]);

                $grandDebit = bcadd($grandDebit, $debitBalance, 2);
                $grandCredit = bcadd($grandCredit, $creditBalance, 2);
            }
        }

        return [
            'accounts' => $rows,
            'total_debit' => $grandDebit,
            'total_credit' => $grandCredit,
            'is_balanced' => bccomp($grandDebit, $grandCredit, 2) === 0,
        ];
    }

    /**
     * Compute Profit and Loss (Income Statement).
     *
     * @return array{revenue_accounts: Collection, total_revenue: string, cogs_accounts: Collection, total_cogs: string, gross_profit: string, expense_accounts: Collection, total_expenses: string, net_profit: string}
     */
    public function getProfitAndLoss(Tenant $tenant, string $startDate, string $endDate): array
    {
        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        $revenueRows = collect();
        $cogsRows = collect();
        $expenseRows = collect();

        $totalRevenue = '0.00';
        $totalCogs = '0.00';
        $totalExpenses = '0.00';

        foreach ($accounts as $account) {
            $lines = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate): void {
                    $q->where('status', 'posted')
                        ->whereDate('entry_date', '>=', $startDate)
                        ->whereDate('entry_date', '<=', $endDate);
                })
                ->get();

            $debit = (string) $lines->sum('debit');
            $credit = (string) $lines->sum('credit');

            if ($account->type === 'revenue') {
                // Revenue: Credit - Debit (contra revenues like discounts reduce revenue)
                $amount = bcsub($credit, $debit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $revenueRows->push([
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => $amount,
                    ]);
                    $totalRevenue = bcadd($totalRevenue, $amount, 2);
                }
            } elseif ($account->sub_type === 'cogs') {
                $amount = bcsub($debit, $credit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $cogsRows->push([
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => $amount,
                    ]);
                    $totalCogs = bcadd($totalCogs, $amount, 2);
                }
            } else {
                // Operating expenses: Debit - Credit
                $amount = bcsub($debit, $credit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $expenseRows->push([
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => $amount,
                    ]);
                    $totalExpenses = bcadd($totalExpenses, $amount, 2);
                }
            }
        }

        $grossProfit = bcsub($totalRevenue, $totalCogs, 2);
        $netProfit = bcsub($grossProfit, $totalExpenses, 2);

        return [
            'revenue_accounts' => $revenueRows,
            'total_revenue' => $totalRevenue,
            'cogs_accounts' => $cogsRows,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expense_accounts' => $expenseRows,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Compute Balance Sheet (Assets = Liabilities + Equity).
     *
     * @return array{asset_accounts: Collection, total_assets: string, liability_accounts: Collection, total_liabilities: string, equity_accounts: Collection, retained_earnings: string, total_equity: string, is_balanced: bool}
     */
    public function getBalanceSheet(Tenant $tenant, ?string $asOfDate = null): array
    {
        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();

        $assetRows = collect();
        $liabilityRows = collect();
        $equityRows = collect();

        $totalAssets = '0.00';
        $totalLiabilities = '0.00';
        $totalEquityBase = '0.00';

        foreach ($accounts as $account) {
            $lines = JournalEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate): void {
                    $q->where('status', 'posted');
                    if ($asOfDate) {
                        $q->whereDate('entry_date', '<=', $asOfDate);
                    }
                })
                ->get();

            $debit = (string) $lines->sum('debit');
            $credit = (string) $lines->sum('credit');

            if ($account->type === 'asset') {
                $amount = bcsub($debit, $credit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $assetRows->push(['code' => $account->code, 'name' => $account->name, 'amount' => $amount]);
                    $totalAssets = bcadd($totalAssets, $amount, 2);
                }
            } elseif ($account->type === 'liability') {
                $amount = bcsub($credit, $debit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $liabilityRows->push(['code' => $account->code, 'name' => $account->name, 'amount' => $amount]);
                    $totalLiabilities = bcadd($totalLiabilities, $amount, 2);
                }
            } elseif ($account->type === 'equity') {
                $amount = bcsub($credit, $debit, 2);
                if (bccomp($amount, '0.00', 2) !== 0) {
                    $equityRows->push(['code' => $account->code, 'name' => $account->name, 'amount' => $amount]);
                    $totalEquityBase = bcadd($totalEquityBase, $amount, 2);
                }
            }
        }

        // Net income from inception to asOfDate flows into Retained Earnings
        $pnl = $this->getProfitAndLoss($tenant, '2000-01-01', $asOfDate ?? now()->toDateString());
        $retainedEarnings = $pnl['net_profit'];

        $totalEquity = bcadd($totalEquityBase, $retainedEarnings, 2);
        $totalLiabAndEquity = bcadd($totalLiabilities, $totalEquity, 2);

        return [
            'asset_accounts' => $assetRows,
            'total_assets' => $totalAssets,
            'liability_accounts' => $liabilityRows,
            'total_liabilities' => $totalLiabilities,
            'equity_accounts' => $equityRows,
            'retained_earnings' => $retainedEarnings,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabAndEquity,
            'is_balanced' => bccomp($totalAssets, $totalLiabAndEquity, 2) === 0,
        ];
    }
}
