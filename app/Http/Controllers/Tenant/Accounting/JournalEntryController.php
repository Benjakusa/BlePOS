<?php

namespace App\Http\Controllers\Tenant\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class JournalEntryController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $query = JournalEntry::query()
            ->where('tenant_id', $tenant->id)
            ->with(['lines.account', 'shop', 'createdByUser'])
            ->latest('entry_date')
            ->latest('id');

        if ($request->filled('ref_type')) {
            $query->where('reference_type', $request->string('ref_type')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $entries = $query->paginate(25);

        return view('tenant.accounting.journals.index', [
            'tenant' => $tenant,
            'entries' => $entries,
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $accounts = Account::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('tenant.accounting.journals.create', [
            'tenant' => $tenant,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $shop = ShopContext::hasShop() ? ShopContext::getShop() : null;

        try {
            $entry = $this->accountingService->recordJournalEntry(
                tenant: $tenant,
                lines: $validated['lines'],
                description: $validated['description'],
                shop: $shop,
                referenceType: 'Manual',
                referenceId: null,
                user: $request->user(),
                entryDate: $validated['entry_date']
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('tenant.accounting.journals.show', $entry)
            ->with('success', "Journal Entry #{$entry->entry_number} posted successfully.");
    }

    public function show(JournalEntry $journal): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null || (int) $journal->tenant_id !== (int) $tenant->id, 403);

        $journal->load(['lines.account', 'shop', 'createdByUser']);

        return view('tenant.accounting.journals.show', [
            'tenant' => $tenant,
            'entry' => $journal,
        ]);
    }
}
