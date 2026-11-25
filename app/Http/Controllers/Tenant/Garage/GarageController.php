<?php

namespace App\Http\Controllers\Tenant\Garage;

use App\Http\Controllers\Controller;
use App\Models\GarageCustomer;
use App\Models\GarageEstimate;
use App\Models\GarageJobCard;
use App\Models\GarageMechanic;
use App\Models\GaragePart;
use App\Models\GaragePartConsumption;
use App\Models\GaragePayment;
use App\Models\GarageVehicle;
use App\Services\ModulePaymentService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function __construct(private readonly ModulePaymentService $payments) {}

    public function index(): View
    {
        return view('tenant.garage.index', [
            'openJobs' => GarageJobCard::whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count(),
            'awaitingApproval' => GarageJobCard::where('status', 'awaiting_approval')->count(),
            'readyForCollection' => GarageJobCard::where('status', 'completed')->count(),
            'lowStockParts' => GaragePart::whereRaw('quantity_on_hand <= reorder_level AND reorder_level > 0')->count(),
        ]);
    }

    public function jobs(): View
    {
        return view('tenant.garage.jobs', ['jobs' => GarageJobCard::with(['vehicle', 'customer'])->paginate(20)]);
    }

    public function createJob(): View
    {
        return view('tenant.garage.jobs-create', [
            'customers' => GarageCustomer::withoutTrashed()->get(),
            'vehicles' => GarageVehicle::withoutTrashed()->get(),
        ]);
    }

    public function storeJob(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'vehicle_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'mileage_in' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);
        $jobNumber = 'JC-'.date('Ymd').'-'.strtoupper(substr(uniqid(), 0, 5));
        GarageJobCard::create($data + ['job_number' => $jobNumber, 'status' => 'received', 'received_at' => now()]);

        return redirect()->route('tenant.garage.jobs')->with('success', 'Job card created.');
    }

    public function showJob(GarageJobCard $jobCard): View
    {
        $jobCard->load(['vehicle', 'customer', 'estimate', 'items', 'payments']);
        $totalPaid = $jobCard->payments->sum('amount');
        $balanceDue = ($jobCard->estimate?->grand_total ?? 0) - $totalPaid;

        return view('tenant.garage.jobs-show', compact('jobCard', 'totalPaid', 'balanceDue'));
    }

    public function advanceStatus(Request $r, GarageJobCard $jobCard): RedirectResponse
    {
        $flow = [
            'received' => 'diagnosing',
            'diagnosing' => 'awaiting_approval',
            'awaiting_approval' => 'approved',
            'approved' => 'in_progress',
            'in_progress' => 'awaiting_parts',
            'awaiting_parts' => 'completed',
            'completed' => 'delivered',
        ];
        $next = $flow[$jobCard->status] ?? null;
        abort_unless($next, 422, 'Cannot advance from '.$jobCard->status);

        if ($next === 'in_progress') {
            $est = $jobCard->estimate;
            abort_unless($est && $est->is_approved, 422, 'Job requires an approved estimate before work can begin.');
        }

        if ($next === 'delivered') {
            $totalPaid = $jobCard->payments->sum('amount');
            $balanceDue = ($jobCard->estimate?->grand_total ?? 0) - $totalPaid;
            if ($balanceDue > 0 && ! $r->user()?->hasPermission('garage.payment.override')) {
                abort(422, 'Outstanding balance of '.number_format($balanceDue, 2).' must be settled before delivery.');
            }
        }

        $jobCard->update(['status' => $next] + ($next === 'delivered' ? ['delivered_at' => now()] : []));

        return back()->with('success', 'Status advanced to '.$next.'.');
    }

    public function storeEstimate(Request $r, GarageJobCard $jobCard): RedirectResponse
    {
        $data = $r->validate([
            'total_parts' => 'required|numeric|min:0',
            'total_labour' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $grand = $data['total_parts'] + $data['total_labour'];
        $est = GarageEstimate::updateOrCreate(
            ['job_id' => $jobCard->id],
            [
                'total_parts' => $data['total_parts'],
                'total_labour' => $data['total_labour'],
                'grand_total' => $grand,
                'is_approved' => false,
                'notes' => $data['notes'] ?? null,
            ]
        );
        $jobCard->update(['estimate_id' => $est->id]);

        return back()->with('success', 'Estimate saved.');
    }

    public function approveEstimate(GarageJobCard $jobCard): RedirectResponse
    {
        abort_unless($jobCard->estimate, 404, 'No estimate found.');
        $jobCard->estimate->update(['is_approved' => true, 'approved_at' => now(), 'approved_by' => auth()->id()]);
        $jobCard->update(['status' => 'approved']);

        return back()->with('success', 'Estimate approved.');
    }

    public function parts(): View
    {
        return view('tenant.garage.parts', ['parts' => GaragePart::withoutTrashed()->paginate(20)]);
    }

    public function createPart(): View
    {
        return view('tenant.garage.parts-create');
    }

    public function storePart(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string',
            'quantity_on_hand' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);
        GaragePart::create($data);

        return redirect()->route('tenant.garage.parts')->with('success', 'Part added.');
    }

    public function consumePart(Request $r, GarageJobCard $jobCard): RedirectResponse
    {
        $data = $r->validate([
            'part_id' => 'required|integer',
            'quantity_used' => 'required|numeric|min:0.001',
        ]);
        $part = GaragePart::findOrFail($data['part_id']);
        abort_if($part->quantity_on_hand < $data['quantity_used'], 422, 'Insufficient stock. Available: '.$part->quantity_on_hand);
        GaragePartConsumption::create([
            'job_id' => $jobCard->id,
            'part_id' => $part->id,
            'quantity_used' => $data['quantity_used'],
            'consumed_at' => now(),
        ]);
        $part->decrement('quantity_on_hand', $data['quantity_used']);

        return back()->with('success', 'Part consumed from stock.');
    }

    public function recordPayment(Request $r, GarageJobCard $jobCard): RedirectResponse
    {
        $data = $r->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['method'], $data['amount'], 'garage', $data['reference'] ?? null);
        GaragePayment::create([
            'job_id' => $jobCard->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'paid_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    public function vehicleHistory(GarageVehicle $vehicle): View
    {
        $jobs = GarageJobCard::where('vehicle_id', $vehicle->id)->with('estimate', 'payments')->orderByDesc('received_at')->paginate(20);

        return view('tenant.garage.vehicle-history', compact('vehicle', 'jobs'));
    }

    public function customers(): View
    {
        return view('tenant.garage.customers', ['customers' => GarageCustomer::withoutTrashed()->paginate(20)]);
    }

    public function createCustomer(): View
    {
        return view('tenant.garage.customers-create');
    }

    public function storeCustomer(Request $r): RedirectResponse
    {
        $data = $r->validate(['name' => 'required|string|max:255', 'phone' => 'required|string|max:20', 'email' => 'nullable|email']);
        GarageCustomer::create($data);

        return redirect()->route('tenant.garage.customers')->with('success', 'Customer added.');
    }

    public function createVehicle(): View
    {
        return view('tenant.garage.vehicles-create', ['customers' => GarageCustomer::all()]);
    }

    public function storeVehicle(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'customer_id' => 'required|integer',
            'plate' => 'required|string|max:20',
            'vin' => 'nullable|string',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'nullable|integer|min:1900|max:2100',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        GarageVehicle::create($data);

        return redirect()->route('tenant.garage.jobs.create')->with('success', 'Vehicle added.');
    }

    public function mechanics(): View
    {
        return view('tenant.garage.mechanics', ['mechanics' => GarageMechanic::withoutTrashed()->paginate(20)]);
    }

    public function createMechanic(): View
    {
        return view('tenant.garage.mechanics-create');
    }

    public function storeMechanic(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);
        GarageMechanic::create($data);

        return redirect()->route('tenant.garage.mechanics')->with('success', 'Mechanic added.');
    }
}
