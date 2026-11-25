<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Expense;
use App\Models\InventoryStock;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TenantAccountingAndGeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Shop $shopA;

    protected Shop $shopB;

    protected User $userA;

    protected User $userB;

    protected Register $registerA;

    protected Register $registerB;

    protected AccountingService $accounting;

    protected SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accounting = app(AccountingService::class);
        $this->saleService = app(SaleService::class);

        // Tenant A
        $this->tenantA = Tenant::create([
            'name' => 'Acme Retail Ltd',
            'code' => 'ACME01',
            'slug' => 'acme-retail',
            'status' => 'active',
            'owner_name' => 'Alice Admin',
            'owner_email' => 'owner@acme.co.ke',
        ]);

        $this->shopA = Shop::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Nairobi CBD Branch',
            'code' => 'NBO-01',
            'currency' => 'KES',
            'is_active' => true,
        ]);

        $this->registerA = Register::create([
            'tenant_id' => $this->tenantA->id,
            'shop_id' => $this->shopA->id,
            'name' => 'Till 1',
            'code' => 'REG-01',
            'is_active' => true,
        ]);

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Alice Accountant',
            'email' => 'alice@acme.co.ke',
            'password' => bcrypt('Password123!'),
        ]);

        $ownerRoleA = TenantRole::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Owner',
            'slug' => 'owner',
            'is_system' => true,
        ]);

        $this->userA->roles()->attach($ownerRoleA);
        $this->userA->shops()->attach($this->shopA, ['is_primary' => true]);

        // Tenant B (for multi-tenant isolation verification)
        $this->tenantB = Tenant::create([
            'name' => 'Beta Pharmacy Ltd',
            'code' => 'BETA01',
            'slug' => 'beta-pharm',
            'status' => 'active',
            'owner_name' => 'Bob Beta',
            'owner_email' => 'owner@beta.co.ke',
        ]);

        $this->shopB = Shop::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Mombasa Branch',
            'code' => 'MSA-01',
            'currency' => 'KES',
            'is_active' => true,
        ]);

        $this->registerB = Register::create([
            'tenant_id' => $this->tenantB->id,
            'shop_id' => $this->shopB->id,
            'name' => 'Till B1',
            'code' => 'REG-B1',
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Bob Beta',
            'email' => 'bob@beta.co.ke',
            'password' => bcrypt('Password123!'),
        ]);

        $ownerRoleB = TenantRole::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Owner',
            'slug' => 'owner',
            'is_system' => true,
        ]);

        $this->userB->roles()->attach($ownerRoleB);
        $this->userB->shops()->attach($this->shopB, ['is_primary' => true]);
    }

    public function test_standard_chart_of_accounts_is_automatically_seeded(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $accounts = Account::where('tenant_id', $this->tenantA->id)->get();

        $this->assertCount(13, $accounts);
        $this->assertTrue($accounts->contains('code', '1010')); // Cash on Hand
        $this->assertTrue($accounts->contains('code', '1020')); // M-Pesa Clearing
        $this->assertTrue($accounts->contains('code', '1030')); // Bank Clearing
        $this->assertTrue($accounts->contains('code', '1050')); // Inventory Asset
        $this->assertTrue($accounts->contains('code', '2020')); // VAT Payable
        $this->assertTrue($accounts->contains('code', '3010')); // Owner's Equity
        $this->assertTrue($accounts->contains('code', '4010')); // Merchandise Sales Revenue
        $this->assertTrue($accounts->contains('code', '5010')); // COGS
        $this->assertTrue($accounts->contains('code', '5020')); // Store Operating Expenses

        // Verify web UI renders chart of accounts
        $response = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->get(route('tenant.accounting.accounts.index'));

        $response->assertOk();
        $response->assertSee('Chart of Accounts');
        $response->assertSee('Cash on Hand');
        $response->assertSee('M-Pesa Clearing Account');
    }

    public function test_double_entry_balance_invariant_rejects_unbalanced_manual_journals(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $cash = Account::where('tenant_id', $this->tenantA->id)->where('code', '1010')->first();
        $equity = Account::where('tenant_id', $this->tenantA->id)->where('code', '3010')->first();

        // Expect exception when total debits != total credits
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unbalanced journal entry');

        $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $cash->id, 'debit' => '50000.00', 'credit' => '0.00'],
                ['account_id' => $equity->id, 'debit' => '0.00', 'credit' => '45000.00'], // Unbalanced!
            ],
            description: 'Capital Contribution',
            user: $this->userA
        );
    }

    public function test_manual_journal_entry_posts_and_updates_account_balances(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $bank = Account::where('tenant_id', $this->tenantA->id)->where('code', '1030')->first();
        $equity = Account::where('tenant_id', $this->tenantA->id)->where('code', '3010')->first();

        $entry = $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $bank->id, 'debit' => '100000.00', 'credit' => '0.00', 'memo' => 'Equity deposit into bank'],
                ['account_id' => $equity->id, 'debit' => '0.00', 'credit' => '100000.00', 'memo' => 'Owner capital contribution'],
            ],
            description: 'Initial Capital Injection',
            shop: $this->shopA,
            referenceType: 'Manual',
            user: $this->userA
        );

        $this->assertNotNull($entry->id);
        $this->assertEquals('posted', $entry->status);
        $this->assertTrue($entry->isBalanced());

        // Check account balances updated
        $bank->refresh();
        $equity->refresh();

        $this->assertEquals('100000.00', $bank->current_balance);
        $this->assertEquals('100000.00', $equity->current_balance);

        // Check detail view
        $response = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->get(route('tenant.accounting.journals.show', $entry));

        $response->assertOk();
        $response->assertSee('Initial Capital Injection');
        $response->assertSee('100,000.00');
    }

    public function test_pos_sale_automatically_creates_balanced_journal_entry(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $category = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Groceries',
            'slug' => 'groceries',
        ]);

        $product = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $category->id,
            'name' => 'Basmati Rice 2kg',
            'sku' => 'RICE-2KG',
            'selling_price' => 500.00,
            'cost_price' => 350.00,
            'tax_rate' => 16.00,
            'tax_type' => 'inclusive',
            'track_stock' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'shop_id' => $this->shopA->id,
            'product_id' => $product->id,
            'quantity' => 50.0000,
        ]);

        $shift = CashierShift::create([
            'tenant_id' => $this->tenantA->id,
            'shop_id' => $this->shopA->id,
            'register_id' => $this->registerA->id,
            'user_id' => $this->userA->id,
            'status' => 'open',
            'opening_float' => 2000.00,
            'opened_at' => now(),
        ]);

        // Process a sale of 2 bags of rice (KES 1,000 total)
        // Cash tender exceeds the server-calculated total by KES 100.
        $sale = $this->saleService->createSale(
            shop: $this->shopA,
            register: $this->registerA,
            user: $this->userA,
            items: [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 500.00,
                ],
            ],
            payments: [['method' => 'cash', 'amount' => 1100.00]],
            shift: $shift
        );

        $this->assertEquals('100.00', $sale->change_due);

        // Verify Journal Entry created automatically
        $journal = JournalEntry::where('tenant_id', $this->tenantA->id)
            ->where('reference_type', 'Sale')
            ->where('reference_id', (string) $sale->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());

        // Check journal lines:
        // Cash Dr: 1,000.00 (1,100 paid - 100 change)
        // Revenue Cr + VAT Cr = 1,000.00
        // COGS Dr: 700.00 (2 * 350 cost)
        // Inventory Cr: 700.00
        $lines = $journal->lines()->with('account')->get();

        $cashLine = $lines->firstWhere('account.code', '1010');
        $cogsLine = $lines->firstWhere('account.code', '5010');
        $invLine = $lines->firstWhere('account.code', '1050');

        $this->assertNotNull($cashLine);
        $this->assertEquals('1000.00', $cashLine->debit);

        $this->assertNotNull($cogsLine);
        $this->assertEquals('700.00', $cogsLine->debit);

        $this->assertNotNull($invLine);
        $this->assertEquals('700.00', $invLine->credit);
    }

    public function test_sale_void_creates_reversing_journal_entry(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $category = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Groceries',
            'slug' => 'groceries-2',
        ]);

        $product = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $category->id,
            'name' => 'Cooking Oil 1L',
            'sku' => 'OIL-1L',
            'selling_price' => 300.00,
            'cost_price' => 200.00,
            'tax_rate' => 16.00,
            'tax_type' => 'inclusive',
            'track_stock' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'shop_id' => $this->shopA->id,
            'product_id' => $product->id,
            'quantity' => 20.0000,
        ]);

        $sale = $this->saleService->createSale(
            shop: $this->shopA,
            register: $this->registerA,
            user: $this->userA,
            items: [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 300.00],
            ],
            payments: [
                ['method' => 'cash', 'amount' => 300.00],
            ]
        );

        // Void the sale
        $this->saleService->voidSale($sale, $this->userA, 'Customer returned merchandise');

        // Verify reversal entry exists
        $reversal = JournalEntry::where('tenant_id', $this->tenantA->id)
            ->where('reference_type', 'SaleVoid')
            ->where('reference_id', (string) $sale->id)
            ->first();

        $this->assertNotNull($reversal);
        $this->assertTrue($reversal->isBalanced());
        $this->assertEquals('posted', $reversal->status);
    }

    public function test_expense_recording_creates_expense_and_posts_journal(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $expenseAccount = Account::where('tenant_id', $this->tenantA->id)->where('code', '5020')->first();
        $cashAccount = Account::where('tenant_id', $this->tenantA->id)->where('code', '1010')->first();

        $response = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->post(route('tenant.accounting.expenses.store'), [
                'expense_date' => now()->toDateString(),
                'category' => 'utilities',
                'description' => 'KPLC Electricity Token',
                'amount' => 1500.00,
                'payment_method' => 'cash',
                'account_id' => $expenseAccount->id,
                'payment_account_id' => $cashAccount->id,
                'receipt_reference' => 'KPLC-84920',
            ]);

        $response->assertRedirect(route('tenant.accounting.expenses.index'));

        $expense = Expense::withoutGlobalScopes()->where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($expense);
        $this->assertEquals('1500.00', $expense->amount);
        $this->assertNotNull($expense->journal_entry_id);

        $journal = JournalEntry::withoutGlobalScopes()->with('lines')->findOrFail($expense->journal_entry_id);
        $this->assertTrue($journal->isBalanced());
        $this->assertEquals('1500.00', $journal->total_debit);
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $cash = Account::where('tenant_id', $this->tenantA->id)->where('code', '1010')->first();
        $equity = Account::where('tenant_id', $this->tenantA->id)->where('code', '3010')->first();

        // Post a balanced journal
        $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $cash->id, 'debit' => '25000.00', 'credit' => '0.00'],
                ['account_id' => $equity->id, 'debit' => '0.00', 'credit' => '25000.00'],
            ],
            description: 'Capital Contribution',
            user: $this->userA
        );

        $tb = $this->accounting->getTrialBalance($this->tenantA);

        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals('25000.00', $tb['total_debit']);
        $this->assertEquals('25000.00', $tb['total_credit']);

        // Test web view
        $response = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->get(route('tenant.accounting.reports.trial-balance'));

        $response->assertOk();
        $response->assertSee('BALANCED');
        $response->assertSee('25,000.00');
    }

    public function test_profit_and_loss_and_balance_sheet_statements(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);

        $bank = Account::where('tenant_id', $this->tenantA->id)->where('code', '1030')->first();
        $revenue = Account::where('tenant_id', $this->tenantA->id)->where('code', '4010')->first();
        $cogs = Account::where('tenant_id', $this->tenantA->id)->where('code', '5010')->first();
        $inv = Account::where('tenant_id', $this->tenantA->id)->where('code', '1050')->first();

        // 1. Initial inventory purchase (Bank -> Inventory)
        $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $inv->id, 'debit' => '10000.00', 'credit' => '0.00'],
                ['account_id' => $bank->id, 'debit' => '0.00', 'credit' => '10000.00'],
            ],
            description: 'Stock purchase',
            user: $this->userA
        );

        // 2. Sale: Revenue 5,000, COGS 3,000
        $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $bank->id, 'debit' => '5000.00', 'credit' => '0.00'],
                ['account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '5000.00'],
                ['account_id' => $cogs->id, 'debit' => '3000.00', 'credit' => '0.00'],
                ['account_id' => $inv->id, 'debit' => '0.00', 'credit' => '3000.00'],
            ],
            description: 'Direct sales invoice',
            user: $this->userA
        );

        $pnl = $this->accounting->getProfitAndLoss($this->tenantA, '2020-01-01', now()->toDateString());

        $this->assertEquals('5000.00', $pnl['total_revenue']);
        $this->assertEquals('3000.00', $pnl['total_cogs']);
        $this->assertEquals('2000.00', $pnl['gross_profit']);
        $this->assertEquals('2000.00', $pnl['net_profit']);

        $bs = $this->accounting->getBalanceSheet($this->tenantA);

        $this->assertTrue($bs['is_balanced']);
        $this->assertEquals('2000.00', $bs['retained_earnings']);

        // Check web views
        $pnlResponse = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->get(route('tenant.accounting.reports.profit-loss'));
        $pnlResponse->assertOk();
        $pnlResponse->assertSee('2,000.00');

        $bsResponse = $this->actingAs($this->userA, 'web')
            ->withSession(['active_shop_id' => $this->shopA->id])
            ->get(route('tenant.accounting.reports.balance-sheet'));
        $bsResponse->assertOk();
        $bsResponse->assertSee('BALANCED');
    }

    public function test_tenant_accounting_isolation(): void
    {
        $this->accounting->seedChartOfAccounts($this->tenantA);
        $this->accounting->seedChartOfAccounts($this->tenantB);

        $cashA = Account::where('tenant_id', $this->tenantA->id)->where('code', '1010')->first();
        $equityA = Account::where('tenant_id', $this->tenantA->id)->where('code', '3010')->first();

        $entryA = $this->accounting->recordJournalEntry(
            tenant: $this->tenantA,
            lines: [
                ['account_id' => $cashA->id, 'debit' => '75000.00', 'credit' => '0.00'],
                ['account_id' => $equityA->id, 'debit' => '0.00', 'credit' => '75000.00'],
            ],
            description: 'Secret Acme Entry',
            user: $this->userA
        );

        // User B from Tenant B cannot access Entry A
        $response = $this->actingAs($this->userB, 'web')
            ->withSession(['active_shop_id' => $this->shopB->id])
            ->get(route('tenant.accounting.journals.show', $entryA));

        $response->assertNotFound();

        // User B's journal index does not show Tenant A's entries
        $listResponse = $this->actingAs($this->userB, 'web')
            ->withSession(['active_shop_id' => $this->shopB->id])
            ->get(route('tenant.accounting.journals.index'));

        $listResponse->assertOk();
        $listResponse->assertDontSee('Secret Acme Entry');
    }
}
