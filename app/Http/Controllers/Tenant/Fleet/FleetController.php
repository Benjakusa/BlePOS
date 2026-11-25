<?php

namespace App\Http\Controllers\Tenant\Fleet;

use App\Http\Controllers\Controller;
use App\Models\FleetBooking;
use App\Models\FleetCustomer;
use App\Models\FleetDriver;
use App\Models\FleetInspection;
use App\Models\FleetMaintenance;
use App\Models\FleetPayment;
use App\Models\FleetVehicle;
use App\Services\ModulePaymentService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FleetController extends Controller
{
    private const BOOKING_BUFFER_HOURS = 2;

    public function __construct(private readonly ModulePaymentService $payments) {}

    public function index(): View
    {
        return view('tenant.fleet.index', [
            'totalVehicles' => FleetVehicle::where('status', '!=', 'inactive')->count(),
            'available' => FleetVehicle::where('status', 'available')->count(),
            'activeBookings' => FleetBooking::where('status', 'active')->count(),
            'inMaintenance' => FleetVehicle::where('status', 'maintenance')->count(),
        ]);
    }

    public function vehicles(): View
    {
        return view('tenant.fleet.vehicles', ['vehicles' => FleetVehicle::withoutTrashed()->paginate(20)]);
    }

    public function createVehicle(): View
    {
        return view('tenant.fleet.vehicles-create');
    }

    public function storeVehicle(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'plate' => 'required|string|max:20',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'nullable|integer',
            'color' => 'nullable|string',
            'type' => 'required|in:car,motorbike,van,truck',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'insurance_expiry' => 'nullable|date',
            'inspection_expiry' => 'nullable|date',
        ]);
        FleetVehicle::create($data);

        return redirect()->route('tenant.fleet.vehicles')->with('success', 'Vehicle added.');
    }

    public function bookings(): View
    {
        return view('tenant.fleet.bookings', ['bookings' => FleetBooking::with(['vehicle', 'customer'])->orderByDesc('pickup_at')->paginate(20)]);
    }

    public function createBooking(): View
    {
        return view('tenant.fleet.bookings-create', [
            'vehicles' => FleetVehicle::where('status', 'available')->get(),
            'customers' => FleetCustomer::withoutTrashed()->get(),
            'drivers' => FleetDriver::where('is_active', true)->get(),
        ]);
    }

    public function storeBooking(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'vehicle_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'driver_id' => 'nullable|integer',
            'pickup_at' => 'required|date',
            'return_at' => 'required|date|after:pickup_at',
            'rate_type' => 'required|in:daily,weekly,custom',
            'agreed_rate' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $vehicle = FleetVehicle::findOrFail($data['vehicle_id']);

        $hasMaintenance = FleetMaintenance::where('vehicle_id', $vehicle->id)->where('is_active', true)->whereNull('completed_at')->exists();
        abort_if($hasMaintenance, 422, 'Vehicle is currently under maintenance and cannot be booked.');

        $pickup = Carbon::parse($data['pickup_at']);
        $return = Carbon::parse($data['return_at']);
        $bufferPickup = $pickup->copy()->subHours(self::BOOKING_BUFFER_HOURS);
        $bufferReturn = $return->copy()->addHours(self::BOOKING_BUFFER_HOURS);

        $overlap = FleetBooking::where('vehicle_id', $vehicle->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('pickup_at', '<', $bufferReturn)
            ->where('return_at', '>', $bufferPickup)
            ->exists();
        abort_if($overlap, 422, 'Vehicle already booked for the selected period (including '.self::BOOKING_BUFFER_HOURS.'h buffer).');

        FleetBooking::create($data);
        $vehicle->update(['status' => 'booked']);

        return redirect()->route('tenant.fleet.bookings')->with('success', 'Booking created.');
    }

    public function recordPayment(Request $r, FleetBooking $booking): RedirectResponse
    {
        $data = $r->validate([
            'type' => 'required|in:deposit,rental,balance',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
        ]);
        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['method'], $data['amount'], 'fleet', $data['reference'] ?? null);

        FleetPayment::create([
            'booking_id' => $booking->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'paid_at' => now(),
        ]);
        if ($data['type'] === 'deposit') {
            $booking->update(['deposit_paid' => true]);
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function completeBooking(Request $r, FleetBooking $booking): RedirectResponse
    {
        $data = $r->validate([
            'actual_return_at' => 'required|date',
            'damage_charge' => 'required|numeric|min:0',
            'fuel_charge' => 'required|numeric|min:0',
        ]);
        $actualReturn = Carbon::parse($data['actual_return_at']);
        $agreedReturn = Carbon::parse($booking->return_at);
        $lateSurcharge = 0;
        if ($actualReturn->gt($agreedReturn)) {
            $lateHours = $agreedReturn->diffInHours($actualReturn);
            $lateDays = ceil($lateHours / 24);
            $lateSurcharge = $lateDays * $booking->agreed_rate;
        }
        $totalBilled = $booking->total_billed ?: $booking->agreed_rate;
        $totalBilled += $lateSurcharge + $data['damage_charge'] + $data['fuel_charge'];

        $booking->update([
            'actual_return_at' => $actualReturn,
            'status' => 'completed',
            'late_surcharge' => $lateSurcharge,
            'damage_charge' => $data['damage_charge'],
            'fuel_charge' => $data['fuel_charge'],
            'total_billed' => $totalBilled,
        ]);

        FleetVehicle::where('id', $booking->vehicle_id)->update(['status' => 'available']);

        return back()->with('success', 'Booking completed. Late surcharge: '.number_format($lateSurcharge, 2).'.');
    }

    public function refundDeposit(Request $r, FleetBooking $booking): RedirectResponse
    {
        abort_unless($booking->deposit_paid && ! $booking->deposit_refunded, 422, 'Deposit not eligible for refund.');
        $deductions = $booking->damage_charge + $booking->fuel_charge;
        $refund = max(0, $booking->deposit_amount - $deductions);
        $booking->update(['deposit_refunded' => true]);

        return back()->with('success', 'Deposit refund of '.number_format($refund, 2).' approved (deductions: '.number_format($deductions, 2).').');
    }

    public function storeInspection(Request $r, FleetBooking $booking): RedirectResponse
    {
        $data = $r->validate([
            'type' => 'required|in:pre,post',
            'fuel_level' => 'nullable|string',
            'odometer' => 'nullable|integer',
            'damage_notes' => 'nullable|string',
        ]);
        FleetInspection::create(['booking_id' => $booking->id, 'inspected_at' => now(), 'inspected_by' => auth()->id()] + $data);

        return back()->with('success', 'Inspection logged.');
    }

    public function maintenance(): View
    {
        return view('tenant.fleet.maintenance', ['records' => FleetMaintenance::with('vehicle')->orderByDesc('started_at')->paginate(20)]);
    }

    public function logMaintenance(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'vehicle_id' => 'required|integer',
            'description' => 'required|string',
            'cost' => 'required|numeric|min:0',
        ]);
        FleetMaintenance::create($data + ['started_at' => now(), 'is_active' => true]);
        FleetVehicle::where('id', $data['vehicle_id'])->update(['status' => 'maintenance']);

        return redirect()->route('tenant.fleet.maintenance')->with('success', 'Maintenance logged. Vehicle marked unavailable.');
    }

    public function completeMaintenance(FleetMaintenance $maintenance): RedirectResponse
    {
        $maintenance->update(['completed_at' => now(), 'is_active' => false]);
        $stillActive = FleetMaintenance::where('vehicle_id', $maintenance->vehicle_id)->where('is_active', true)->whereNull('completed_at')->exists();
        if (! $stillActive) {
            FleetVehicle::where('id', $maintenance->vehicle_id)->update(['status' => 'available']);
        }

        return back()->with('success', 'Maintenance completed. Vehicle available.');
    }

    public function customers(): View
    {
        return view('tenant.fleet.customers', ['customers' => FleetCustomer::withoutTrashed()->paginate(20)]);
    }

    public function createCustomer(): View
    {
        return view('tenant.fleet.customers-create');
    }

    public function storeCustomer(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'id_number' => 'nullable|string',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
            'email' => 'nullable|email',
        ]);
        FleetCustomer::create($data);

        return redirect()->route('tenant.fleet.customers')->with('success', 'Customer added.');
    }

    public function drivers(): View
    {
        return view('tenant.fleet.drivers', ['drivers' => FleetDriver::withoutTrashed()->paginate(20)]);
    }

    public function createDriver(): View
    {
        return view('tenant.fleet.drivers-create');
    }

    public function storeDriver(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'license_number' => 'nullable|string',
            'license_expiry' => 'nullable|date',
        ]);
        FleetDriver::create($data);

        return redirect()->route('tenant.fleet.drivers')->with('success', 'Driver added.');
    }
}
