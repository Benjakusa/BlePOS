<?php

namespace App\Http\Controllers\Tenant\Printing;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialConsumption;
use App\Models\PrintPayment;
use App\Models\PrintQuote;
use App\Services\ModulePaymentService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintingController extends Controller
{
    public function __construct(private readonly ModulePaymentService $payments) {}

    public function index(): View
    {
        return view('tenant.printing.index', [
            'totalJobs' => PrintJob::count(),
            'inProduction' => PrintJob::where('status', 'in_production')->count(),
            'readyForCollection' => PrintJob::where('status', 'ready')->count(),
            'lowStockMaterials' => PrintMaterial::whereRaw('quantity_on_hand <= reorder_level')->count(),
        ]);
    }

    // Jobs list
    public function jobs(): View
    {
        return view('tenant.printing.jobs', [
            'jobs' => PrintJob::with('payments')->paginate(20),
        ]);
    }

    // Create job
    public function createJob(): View
    {
        return view('tenant.printing.jobs-create');
    }

    public function storeJob(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        PrintJob::create($data + [
            'status' => 'quote',
            'total_amount' => 0,
            'balance_due' => 0,
            'deposit_amount' => 0,
        ]);

        return redirect()->route('tenant.printing.jobs')->with('success', 'Job created.');
    }

    // Add quote to job
    public function storeQuote(Request $r, PrintJob $job): RedirectResponse
    {
        $data = $r->validate([
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Invalidate previous quotes
        PrintQuote::where('job_id', $job->id)->update(['is_accepted' => false]);

        $version = (PrintQuote::where('job_id', $job->id)->max('version') ?? 0) + 1;

        PrintQuote::create([
            'job_id' => $job->id,
            'version' => $version,
            'subtotal' => $data['subtotal'],
            'total' => $data['total'],
            'notes' => $data['notes'] ?? null,
        ]);

        $job->update([
            'total_amount' => $data['total'],
            'balance_due' => $data['total'] - $job->deposit_amount,
            'status' => 'quote',
        ]);

        return back()->with('success', 'Quote v'.$version.' created.');
    }

    // Accept quote
    public function acceptQuote(Request $r, PrintJob $job): RedirectResponse
    {
        $quote = PrintQuote::where('job_id', $job->id)->latest()->firstOrFail();
        $quote->update(['is_accepted' => true, 'accepted_at' => now()]);
        $job->update(['status' => 'approved']);

        return back()->with('success', 'Quote accepted. Job approved.');
    }

    // Record deposit payment
    public function recordDeposit(Request $r, PrintJob $job): RedirectResponse
    {
        $data = $r->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
        ]);

        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment(
            $tenant->id,
            TenantContext::getShopId(),
            $data['method'],
            $data['amount'],
            'printing',
            $data['reference'] ?? null
        );

        PrintPayment::create([
            'job_id' => $job->id,
            'type' => 'deposit',
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'paid_at' => now(),
        ]);

        $newDeposit = $job->deposit_amount + $data['amount'];
        $job->update([
            'deposit_amount' => $newDeposit,
            'deposit_paid_at' => now(),
            'balance_due' => max(0, $job->total_amount - $newDeposit),
        ]);

        return back()->with('success', 'Deposit recorded.');
    }

    // Advance to in_production (requires accepted quote + deposit)
    public function startProduction(PrintJob $job): RedirectResponse
    {
        $acceptedQuote = PrintQuote::where('job_id', $job->id)->where('is_accepted', true)->first();
        abort_unless($acceptedQuote, 422, 'Job requires an accepted quote before production.');
        abort_unless($job->deposit_amount > 0, 422, 'A deposit payment must be recorded before production.');

        // Deduct materials consumed
        foreach (PrintMaterialConsumption::where('job_id', $job->id)->get() as $c) {
            PrintMaterial::where('id', $c->material_id)->decrement('quantity_on_hand', $c->quantity_used);
        }

        $job->update(['status' => 'in_production']);

        return back()->with('success', 'Production started. Materials deducted from stock.');
    }

    // Advance status
    public function advanceStatus(Request $r, PrintJob $job): RedirectResponse
    {
        $flow = [
            'quote' => 'approved',
            'approved' => 'in_design',
            'in_design' => 'in_production',
            'in_production' => 'ready',
            'ready' => 'delivered',
        ];

        $next = $flow[$job->status] ?? null;
        abort_unless($next, 422, 'Cannot advance from '.$job->status);

        if ($next === 'in_production') {
            return $this->startProduction($job);
        }

        $job->update(['status' => $next] + ($next === 'delivered' ? ['delivered_at' => now()] : []));

        return back()->with('success', 'Status updated to '.$next.'.');
    }

    // Materials
    public function materials(): View
    {
        return view('tenant.printing.materials', [
            'materials' => PrintMaterial::withoutTrashed()->paginate(20),
        ]);
    }

    public function createMaterial(): View
    {
        return view('tenant.printing.materials-create');
    }

    public function storeMaterial(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        PrintMaterial::create($data);

        return redirect()->route('tenant.printing.materials')->with('success', 'Material added.');
    }
}
