<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\FleetBooking;
use App\Models\FleetCustomer;
use App\Models\FleetPayment;
use App\Models\FleetVehicle;
use App\Models\GarageCustomer;
use App\Models\GarageEstimate;
use App\Models\GarageJobCard;
use App\Models\GaragePayment;
use App\Models\GarageVehicle;
use App\Models\GymCheckin;
use App\Models\GymMember;
use App\Models\GymPayment;
use App\Models\GymPlan;
use App\Models\GymSubscription;
use App\Models\MpesaTransaction;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Services\ModuleService;
use App\Services\MpesaStkPushService;
use App\Services\SaleService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly ModuleService $moduleService,
    ) {}

    /**
     * POS checkout screen. Supports industry-specific formatted terminal modes.
     * Requires an open cashier shift.
     */
    public function checkout(Request $request): View|RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403, 'Cashier permission required.');

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $openShift) {
            return redirect()->route('tenant.shifts.index')
                ->with('warning', 'You must open a cashier shift before processing sales.');
        }

        // Available modes for POS terminal strictly scoped to active industry modules
        $availableModes = [];

        if ($this->moduleService->isModuleAccessible($tenant, 'gym')) {
            $availableModes['gym'] = ['label' => 'Gym Front Desk', 'icon' => 'bi-activity'];
        }

        if ($this->moduleService->isModuleAccessible($tenant, 'parcels')) {
            $availableModes['parcels'] = ['label' => 'Parcel Logistics', 'icon' => 'bi-box-seam'];
        }

        if ($this->moduleService->isModuleAccessible($tenant, 'fleet')) {
            $availableModes['fleet'] = ['label' => 'Fleet Dispatch', 'icon' => 'bi-car-front'];
        }

        if ($this->moduleService->isModuleAccessible($tenant, 'garage')) {
            $availableModes['garage'] = ['label' => 'Auto Garage Service', 'icon' => 'bi-tools'];
        }

        // Retail checkout mode (standard POS catalog sale)
        // Available for general retail/wholesale/hospitality or tenants with product sales enabled
        if (empty($availableModes) || $this->moduleService->isModuleAccessible($tenant, 'mod-products') || in_array($tenant->industry, ['retail', 'wholesale', 'restaurant', 'hotel', 'pharmacy', 'agrovet', 'printing-branding', 'general'], true)) {
            $availableModes['retail'] = ['label' => 'Retail Checkout', 'icon' => 'bi-cart3'];
        }

        $defaultMode = match ($tenant->industry) {
            'parcel-delivery' => 'parcels',
            'gym-fitness' => 'gym',
            'car-hire-fleet' => 'fleet',
            'auto-garage' => 'garage',
            default => array_key_first($availableModes) ?? 'retail',
        };

        if (! array_key_exists($defaultMode, $availableModes)) {
            $defaultMode = array_key_first($availableModes) ?? 'retail';
        }

        $requestedMode = $request->query('mode');
        if ($requestedMode) {
            abort_unless(array_key_exists($requestedMode, $availableModes), 403, "The requested POS mode '{$requestedMode}' is not available for your industry.");
            $mode = $requestedMode;
        } else {
            $mode = $defaultMode;
        }

        $registers = Register::query()->get();
        $tables = DiningTable::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = Category::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $destinationShops = $mode === 'parcels' ? Shop::where('tenant_id', $tenant->id)->where('is_active', true)->get() : collect();
        $recentParcels = $mode === 'parcels' ? Parcel::where('tenant_id', $tenant->id)->latest()->limit(10)->get() : collect();

        $gymPlans = $mode === 'gym' ? GymPlan::where('tenant_id', $tenant->id)->where('is_active', true)->get() : collect();
        $gymMembers = $mode === 'gym' ? GymMember::where('tenant_id', $tenant->id)->latest()->limit(50)->get() : collect();
        $recentCheckins = $mode === 'gym' ? GymCheckin::where('tenant_id', $tenant->id)->with('member')->latest('checked_in_at')->limit(10)->get() : collect();

        $availableVehicles = $mode === 'fleet' ? FleetVehicle::where('tenant_id', $tenant->id)->where('status', 'available')->get() : collect();
        $fleetCustomers = $mode === 'fleet' ? FleetCustomer::where('tenant_id', $tenant->id)->latest()->limit(50)->get() : collect();
        $recentBookings = $mode === 'fleet' ? FleetBooking::where('tenant_id', $tenant->id)->with(['vehicle', 'customer'])->latest()->limit(10)->get() : collect();

        $garageVehicles = $mode === 'garage' ? GarageVehicle::where('tenant_id', $tenant->id)->with('customer')->latest()->limit(50)->get() : collect();
        $garageCustomers = $mode === 'garage' ? GarageCustomer::where('tenant_id', $tenant->id)->latest()->limit(50)->get() : collect();
        $recentJobCards = $mode === 'garage' ? GarageJobCard::where('tenant_id', $tenant->id)->with(['vehicle', 'customer', 'estimate'])->latest()->limit(10)->get() : collect();

        return view('tenant.pos.checkout', [
            'tenant' => $tenant,
            'openShift' => $openShift,
            'mode' => $mode,
            'availableModes' => $availableModes,
            'registers' => $registers,
            'tables' => $tables,
            'categories' => $categories,
            'destinationShops' => $destinationShops,
            'recentParcels' => $recentParcels,
            'gymPlans' => $gymPlans,
            'gymMembers' => $gymMembers,
            'recentCheckins' => $recentCheckins,
            'availableVehicles' => $availableVehicles,
            'fleetCustomers' => $fleetCustomers,
            'recentBookings' => $recentBookings,
            'garageVehicles' => $garageVehicles,
            'garageCustomers' => $garageCustomers,
            'recentJobCards' => $recentJobCards,
        ]);
    }

    /**
     * Process a completed sale. Accepts JSON payload from the POS screen.
     */
    public function processSale(Request $request): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 100, 422, 'A valid Idempotency-Key header is required.');

        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:cashier_shifts,id'],
            'register_id' => ['nullable', 'integer', 'exists:registers,id'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.batch_id' => ['nullable', 'integer'],
            'items.*.prescription_id' => ['nullable', 'integer'],
            'items.*.pharmacist_user_id' => ['nullable', 'integer'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,mpesa,card,cheque,credit'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);
        abort_if(collect($validated['items'])->contains(fn ($item) => ! empty($item['pharmacist_user_id']) && (int) $item['pharmacist_user_id'] !== (int) $request->user()->id), 403, 'The dispensing pharmacist must authenticate as the cashier performing this checkout.');

        $shift = CashierShift::query()->findOrFail($validated['shift_id']);
        abort_if((int) $shift->tenant_id !== (int) $tenant->id, 403);
        abort_if($shift->status !== 'open', 422, 'Cashier shift is not open.');
        abort_if((int) $shift->user_id !== $request->user()->id, 403, 'Shift does not belong to you.');

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_if((int) $shop->tenant_id !== (int) $tenant->id, 403, 'Shop does not belong to this business.');

        $register = $validated['register_id']
            ? Register::query()->findOrFail($validated['register_id'])
            : Register::query()->where('shop_id', $shop->id)->firstOrFail();
        abort_unless((int) $register->shop_id === (int) $shop->id, 422, 'Register does not belong to this shop.');
        abort_unless($request->user()->canAccessShop($shop), 403, 'You are not assigned to this shop.');
        $hasDiscount = collect($validated['items'])->contains(fn ($item) => bccomp((string) ($item['discount'] ?? '0.00'), '0.00', 2) > 0);
        abort_if($hasDiscount
            && ! $request->user()->hasPermission('pos.discount.apply')
            && ! $request->user()->hasPermission('pos.sale.discount')
            && ! $request->user()->hasPermission('pos.discount.override'), 403, 'Discount permission required.');
        abort_if(collect($validated['payments'])->contains(fn ($payment) => $payment['method'] === 'credit')
            && ! $request->user()->hasPermission('pos.sale.credit'), 403, 'Credit sale permission required.');

        $sale = $this->saleService->createSale(
            shop: $shop,
            register: $register,
            shift: $shift,
            user: $request->user(),
            items: $validated['items'],
            payments: $validated['payments'],
            diningTableId: $validated['dining_table_id'] ?? null,
            notes: $validated['notes'] ?? null,
            idempotencyKey: $idempotencyKey
        );

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'receipt_number' => $sale->receipt_number,
            'total_amount' => $sale->total_amount,
            'grand_total' => $sale->grand_total,
            'change_due' => $sale->change_due,
            'receipt_url' => route('tenant.pos.receipt', $sale),
        ]);
    }

    /**
     * Process parcel intake at the POS counter and balance cashier shift.
     */
    public function processParcelIntake(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'parcel-delivery'), 403, 'Parcel logistics module is not enabled for your industry.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();
        abort_if(! $openShift, 422, 'Cashier shift is not open.');

        $validated = $request->validate([
            'destination_shop_id' => ['required', 'integer', 'exists:shops,id'],
            'sender_name' => ['required', 'string', 'max:120'],
            'sender_phone' => ['required', 'string', 'max:32'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:32'],
            'contents' => ['required', 'string', 'max:255'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'service_level' => ['required', 'in:standard,express'],
            'fee' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,mpesa,pod'],
        ]);

        $trackingNumber = 'WB-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));

        $parcel = Parcel::create([
            'tenant_id' => $tenant->id,
            'tracking_number' => $trackingNumber,
            'origin_shop_id' => $openShift->shop_id,
            'current_shop_id' => $openShift->shop_id,
            'destination_shop_id' => $validated['destination_shop_id'],
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $validated['sender_phone'],
            'recipient_name' => $validated['recipient_name'],
            'recipient_phone' => $validated['recipient_phone'],
            'contents' => $validated['contents'],
            'weight_kg' => $validated['weight_kg'] ?? 1.0,
            'service_level' => $validated['service_level'],
            'fee' => $validated['fee'],
            'status' => 'received',
            'received_by' => $request->user()->id,
            'received_at' => now(),
        ]);

        ParcelEvent::create([
            'tenant_id' => $tenant->id,
            'parcel_id' => $parcel->id,
            'event_type' => 'received_at_origin',
            'from_status' => null,
            'to_status' => 'received',
            'shop_id' => $openShift->shop_id,
            'user_id' => $request->user()->id,
            'note' => 'Intake at POS terminal. Payment: '.strtoupper($validated['payment_method']),
            'occurred_at' => now(),
        ]);

        $feeStr = number_format((float) $validated['fee'], 2, '.', '');
        if ($validated['payment_method'] === 'cash') {
            $openShift->cash_sales = bcadd((string) $openShift->cash_sales, $feeStr, 2);
            $openShift->expected_cash = $openShift->calculateExpectedCash();
            $openShift->save();
        } elseif ($validated['payment_method'] === 'mpesa') {
            $openShift->mpesa_sales = bcadd((string) $openShift->mpesa_sales, $feeStr, 2);
            $openShift->save();
        }
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tracking_number' => $trackingNumber,
                'parcel_id' => $parcel->id,
                'waybill_url' => route('tenant.pos.waybill', $parcel),
            ]);
        }

        return redirect()->route('tenant.pos.waybill', $parcel)
            ->with('success', "Parcel registered successfully with Waybill #{$trackingNumber}.");
    }

    /**
     * Printable parcel waybill thermal receipt.
     */
    public function waybill(Parcel $parcel): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'parcel-delivery'), 403, 'Parcel logistics module is not enabled for your industry.');
        abort_if((int) $parcel->tenant_id !== (int) $tenant->id, 403);

        $parcel->load(['originOffice', 'destinationOffice', 'receivedBy']);

        return view('tenant.pos.slips.waybill', [
            'tenant' => $tenant,
            'parcel' => $parcel,
        ]);
    }

    /**
     * Process Gym Front Desk operations (Check-in, Day pass, Membership plan sales).
     */
    public function processGymAction(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'gym'), 403, 'Gym module is not enabled for your industry.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();
        abort_if(! $openShift, 422, 'Cashier shift is not open.');

        $actionType = $request->input('action_type', 'checkin');

        if ($actionType === 'checkin') {
            $validated = $request->validate([
                'member_id' => ['required', 'integer', 'exists:gym_members,id'],
            ]);

            $member = GymMember::where('tenant_id', $tenant->id)->findOrFail($validated['member_id']);

            $subscription = GymSubscription::where('tenant_id', $tenant->id)
                ->where('member_id', $member->id)
                ->where('status', 'active')
                ->where('start_date', '<=', today())
                ->where('end_date', '>=', today())
                ->first();

            if ($subscription && $subscription->session_credits_remaining !== null) {
                if ($subscription->session_credits_remaining <= 0) {
                    return back()->with('error', 'Member has 0 remaining session credits.');
                }
                $subscription->decrement('session_credits_remaining');
            }

            GymCheckin::create([
                'tenant_id' => $tenant->id,
                'shop_id' => $openShift->shop_id,
                'member_id' => $member->id,
                'checked_in_at' => now(),
                'method' => 'manual',
            ]);

            return back()->with('success', "Member {$member->name} checked in successfully.");
        }

        if ($actionType === 'day_pass') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:32'],
                'amount' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'in:cash,mpesa'],
            ]);

            $member = GymMember::firstOrCreate(
                ['tenant_id' => $tenant->id, 'phone' => $validated['phone']],
                ['name' => $validated['name'], 'shop_id' => $openShift->shop_id]
            );

            $plan = GymPlan::where('tenant_id', $tenant->id)->firstOrCreate(
                ['name' => 'Day Pass'],
                ['shop_id' => $openShift->shop_id, 'billing_cycle' => 'sessions', 'price' => $validated['amount'], 'session_credits' => 1]
            );

            $subscription = GymSubscription::create([
                'tenant_id' => $tenant->id,
                'shop_id' => $openShift->shop_id,
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'start_date' => today(),
                'end_date' => today(),
                'session_credits_remaining' => 0,
            ]);

            $payment = GymPayment::create([
                'tenant_id' => $tenant->id,
                'shop_id' => $openShift->shop_id,
                'subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'amount' => $validated['amount'],
                'method' => $validated['payment_method'],
                'reference' => 'PASS-'.strtoupper(Str::random(6)),
                'paid_at' => now(),
                'notes' => 'Walk-in Day Pass at POS Terminal',
            ]);

            GymCheckin::create([
                'tenant_id' => $tenant->id,
                'shop_id' => $openShift->shop_id,
                'member_id' => $member->id,
                'checked_in_at' => now(),
                'method' => 'manual',
            ]);

            $amountStr = number_format((float) $validated['amount'], 2, '.', '');
            if ($validated['payment_method'] === 'cash') {
                $openShift->cash_sales = bcadd((string) $openShift->cash_sales, $amountStr, 2);
                $openShift->expected_cash = $openShift->calculateExpectedCash();
                $openShift->save();
            } else {
                $openShift->mpesa_sales = bcadd((string) $openShift->mpesa_sales, $amountStr, 2);
                $openShift->save();
            }

            return redirect()->route('tenant.pos.gym-slip', $payment)
                ->with('success', "Day Pass issued for {$member->name}.");
        }

        // Plan Sale
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:gym_members,id'],
            'plan_id' => ['required', 'integer', 'exists:gym_plans,id'],
            'payment_method' => ['required', 'in:cash,mpesa'],
        ]);

        $member = GymMember::where('tenant_id', $tenant->id)->findOrFail($validated['member_id']);
        $plan = GymPlan::where('tenant_id', $tenant->id)->findOrFail($validated['plan_id']);

        $durationDays = match ($plan->billing_cycle) {
            'weekly' => 7,
            'sessions' => 90,
            default => 30,
        };

        $subscription = GymSubscription::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => today(),
            'end_date' => today()->addDays($durationDays),
            'session_credits_remaining' => $plan->session_credits,
        ]);

        $payment = GymPayment::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'subscription_id' => $subscription->id,
            'member_id' => $member->id,
            'amount' => $plan->price,
            'method' => $validated['payment_method'],
            'reference' => 'SUB-'.strtoupper(Str::random(6)),
            'paid_at' => now(),
            'notes' => "Membership Plan '{$plan->name}' enrolled via POS",
        ]);

        $amountStr = number_format((float) $plan->price, 2, '.', '');
        if ($validated['payment_method'] === 'cash') {
            $openShift->cash_sales = bcadd((string) $openShift->cash_sales, $amountStr, 2);
            $openShift->expected_cash = $openShift->calculateExpectedCash();
            $openShift->save();
        } else {
            $openShift->mpesa_sales = bcadd((string) $openShift->mpesa_sales, $amountStr, 2);
            $openShift->save();
        }

        return redirect()->route('tenant.pos.gym-slip', $payment)
            ->with('success', "Membership plan {$plan->name} sold to {$member->name}.");
    }

    /**
     * Printable Gym admission / day pass receipt slip.
     */
    public function gymSlip(GymPayment $payment): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'gym'), 403, 'Gym module is not enabled for your industry.');
        abort_if((int) $payment->tenant_id !== (int) $tenant->id, 403);

        $payment->load(['member', 'subscription.plan']);

        return view('tenant.pos.slips.gym', [
            'tenant' => $tenant,
            'payment' => $payment,
        ]);
    }

    /**
     * Process vehicle dispatch & deposit intake for Fleet Car Hire.
     */
    public function processFleetDispatch(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'fleet'), 403, 'Fleet module is not enabled for your industry.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();
        abort_if(! $openShift, 422, 'Cashier shift is not open.');

        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:fleet_vehicles,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'id_number' => ['nullable', 'string', 'max:32'],
            'license_number' => ['nullable', 'string', 'max:32'],
            'pickup_at' => ['required', 'date'],
            'return_at' => ['required', 'date', 'after:pickup_at'],
            'agreed_rate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,mpesa'],
        ]);

        $vehicle = FleetVehicle::where('tenant_id', $tenant->id)->findOrFail($validated['vehicle_id']);
        abort_if($vehicle->status !== 'available', 422, 'Selected vehicle is not available.');

        $customer = FleetCustomer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'shop_id' => $openShift->shop_id,
                'id_number' => $validated['id_number'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
            ]
        );

        $pickup = Carbon::parse($validated['pickup_at']);
        $return = Carbon::parse($validated['return_at']);
        $days = max(1, $pickup->diffInDays($return) ?: 1);
        $totalRental = (float) $validated['agreed_rate'] * $days;
        $totalBilled = $totalRental + (float) $validated['deposit_amount'];

        $booking = FleetBooking::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'pickup_at' => $validated['pickup_at'],
            'return_at' => $validated['return_at'],
            'rate_type' => 'daily',
            'agreed_rate' => $validated['agreed_rate'],
            'deposit_amount' => $validated['deposit_amount'],
            'deposit_paid' => true,
            'total_billed' => $totalBilled,
            'status' => 'active',
        ]);

        $vehicle->update(['status' => 'booked']);

        FleetPayment::create([
            'booking_id' => $booking->id,
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'type' => 'deposit',
            'amount' => $validated['deposit_amount'],
            'method' => $validated['payment_method'],
            'reference' => 'DEP-'.strtoupper(Str::random(6)),
            'paid_at' => now(),
        ]);

        $depositStr = number_format((float) $validated['deposit_amount'], 2, '.', '');
        if ($validated['payment_method'] === 'cash') {
            $openShift->cash_sales = bcadd((string) $openShift->cash_sales, $depositStr, 2);
            $openShift->expected_cash = $openShift->calculateExpectedCash();
            $openShift->save();
        } else {
            $openShift->mpesa_sales = bcadd((string) $openShift->mpesa_sales, $depositStr, 2);
            $openShift->save();
        }

        return redirect()->route('tenant.pos.fleet-slip', $booking)
            ->with('success', "Vehicle {$vehicle->plate} dispatched to {$customer->name}.");
    }

    /**
     * Printable Fleet Gate Pass & Handover Agreement slip.
     */
    public function fleetSlip(FleetBooking $booking): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'fleet'), 403, 'Fleet module is not enabled for your industry.');
        abort_if((int) $booking->tenant_id !== (int) $tenant->id, 403);

        $booking->load(['vehicle', 'customer', 'payments']);

        return view('tenant.pos.slips.fleet', [
            'tenant' => $tenant,
            'booking' => $booking,
        ]);
    }

    /**
     * Process vehicle intake and service counter job card creation for Auto Garage.
     */
    public function processGarageIntake(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'garage'), 403, 'Auto garage module is not enabled for your industry.');
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);

        $openShift = CashierShift::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();
        abort_if(! $openShift, 422, 'Cashier shift is not open.');

        $validated = $request->validate([
            'plate' => ['required', 'string', 'max:20'],
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'mileage_in' => ['nullable', 'integer', 'min:0'],
            'service_notes' => ['required', 'string', 'max:500'],
            'labour_estimate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,mpesa'],
        ]);

        $customer = GarageCustomer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $validated['customer_phone']],
            ['name' => $validated['customer_name'], 'shop_id' => $openShift->shop_id]
        );

        $vehicle = GarageVehicle::firstOrCreate(
            ['tenant_id' => $tenant->id, 'plate' => strtoupper(trim($validated['plate']))],
            [
                'customer_id' => $customer->id,
                'shop_id' => $openShift->shop_id,
                'make' => $validated['make'],
                'model' => $validated['model'],
            ]
        );

        $jobNumber = 'JC-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

        $jobCard = GarageJobCard::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'vehicle_id' => $vehicle->id,
            'customer_id' => $customer->id,
            'job_number' => $jobNumber,
            'status' => 'received',
            'mileage_in' => $validated['mileage_in'] ?? null,
            'notes' => $validated['service_notes'],
            'received_at' => now(),
        ]);

        $estimate = GarageEstimate::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $openShift->shop_id,
            'job_id' => $jobCard->id,
            'total_labour' => $validated['labour_estimate'],
            'total_parts' => 0,
            'grand_total' => $validated['labour_estimate'],
            'notes' => 'Initial estimate at POS counter',
        ]);

        $jobCard->update(['estimate_id' => $estimate->id]);

        $deposit = (float) ($validated['deposit_amount'] ?? 0);
        if ($deposit > 0 && ! empty($validated['payment_method'])) {
            GaragePayment::create([
                'tenant_id' => $tenant->id,
                'shop_id' => $openShift->shop_id,
                'job_id' => $jobCard->id,
                'amount' => $deposit,
                'method' => $validated['payment_method'],
                'reference' => 'GAR-'.strtoupper(Str::random(6)),
                'paid_at' => now(),
                'notes' => 'Intake deposit at POS',
            ]);

            $depositStr = number_format($deposit, 2, '.', '');
            if ($validated['payment_method'] === 'cash') {
                $openShift->cash_sales = bcadd((string) $openShift->cash_sales, $depositStr, 2);
                $openShift->expected_cash = $openShift->calculateExpectedCash();
                $openShift->save();
            } else {
                $openShift->mpesa_sales = bcadd((string) $openShift->mpesa_sales, $depositStr, 2);
                $openShift->save();
            }
        }

        return redirect()->route('tenant.pos.garage-slip', $jobCard)
            ->with('success', "Job card #{$jobNumber} created for vehicle {$vehicle->plate}.");
    }

    /**
     * Printable Auto Garage Job Card Work Order slip.
     */
    public function garageSlip(GarageJobCard $jobCard): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($this->moduleService->isModuleAccessible($tenant, 'garage'), 403, 'Auto garage module is not enabled for your industry.');
        abort_if((int) $jobCard->tenant_id !== (int) $tenant->id, 403);

        $jobCard->load(['vehicle', 'customer', 'estimate', 'payments']);

        return view('tenant.pos.slips.garage', [
            'tenant' => $tenant,
            'jobCard' => $jobCard,
        ]);
    }

    /**
     * Trigger M-Pesa STK Push from the checkout screen.
     */
    public function initiateMpesaStkPush(Request $request, MpesaStkPushService $stkPush): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.create'), 403);
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        abort_if($idempotencyKey === '' || strlen($idempotencyKey) > 100, 422, 'A valid Idempotency-Key header is required.');

        $validated = $request->validate([
            'shift_id' => ['required', 'integer', 'exists:cashier_shifts,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'register_id' => ['required', 'integer', 'exists:registers,id'],
            'dining_table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.batch_id' => ['nullable', 'integer'],
            'items.*.prescription_id' => ['nullable', 'integer'],
            'items.*.pharmacist_user_id' => ['nullable', 'integer'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'phone_number' => ['required', 'string', 'min:9', 'max:20'],
        ]);
        abort_if(collect($validated['items'])->contains(fn ($item) => ! empty($item['pharmacist_user_id']) && (int) $item['pharmacist_user_id'] !== (int) $request->user()->id), 403, 'The dispensing pharmacist must authenticate as the cashier performing this checkout.');

        $shift = CashierShift::query()->findOrFail($validated['shift_id']);
        abort_unless((int) $shift->tenant_id === (int) $tenant->id
            && (int) $shift->user_id === (int) $request->user()->id
            && $shift->status === 'open', 403, 'An open cashier shift assigned to you is required.');
        $shop = Shop::query()->findOrFail($validated['shop_id']);
        abort_unless((int) $shop->tenant_id === (int) $tenant->id && $request->user()->canAccessShop($shop), 403, 'Shop does not belong to this business or is not assigned to you.');
        abort_unless((int) $shift->shop_id === (int) $shop->id, 422, 'Cashier shift belongs to another shop.');
        $register = Register::query()->findOrFail($validated['register_id']);
        abort_unless((int) $register->shop_id === (int) $shop->id, 422, 'Register does not belong to this shop.');

        $sale = $this->saleService->createPendingMpesaSale(
            shop: $shop,
            register: $register,
            user: $request->user(),
            items: $validated['items'],
            shift: $shift,
            notes: $validated['notes'] ?? null,
            diningTableId: $validated['dining_table_id'] ?? null,
            idempotencyKey: $idempotencyKey,
        );
        abort_if(MpesaTransaction::query()->where('sale_id', $sale->id)->where('status', 'pending')->exists(), 409, 'An STK request is already pending for this checkout.');

        try {
            $result = $stkPush->initiate(
                tenantId: $tenant->id,
                branchId: $shop->id,
                phoneNumber: $validated['phone_number'],
                amount: (float) $sale->grand_total,
                accountReference: $sale->sale_number,
                description: 'POS Sale',
                saleId: $sale->id,
            );
        } catch (\Throwable $exception) {
            $hasPendingAttempt = MpesaTransaction::query()->where('sale_id', $sale->id)->where('status', 'pending')->exists();
            if (! $hasPendingAttempt) {
                $this->saleService->releasePendingMpesaStock($sale);
            }
            if ($hasPendingAttempt) {
                return response()->json(['success' => false, 'customer_message' => 'An M-Pesa prompt is already active for this checkout. Check its status before retrying.'], 409);
            }

            return response()->json(['success' => false, 'customer_message' => 'M-Pesa request could not be started. You may retry this checkout.'], 422);
        }

        if ($result['status'] !== 'initiated') {
            $this->saleService->releasePendingMpesaStock($sale);
        }

        return response()->json($result + [
            'success' => $result['status'] === 'initiated',
            'sale_id' => $sale->id,
            'total_amount' => $sale->grand_total,
        ], $result['status'] === 'initiated' ? 202 : 422);
    }

    public function mpesaStkStatus(Request $request, MpesaTransaction $transaction): JsonResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless((int) $transaction->tenant_id === (int) $tenant->id && $transaction->sale_id, 404);
        $sale = Sale::withoutGlobalScopes()->findOrFail($transaction->sale_id);
        abort_unless((int) $sale->user_id === (int) $request->user()->id || $request->user()->hasPermission('pos.sales.view'), 403);

        return response()->json([
            'transaction_status' => $transaction->status,
            'sale_status' => $sale->status,
            'receipt_url' => $sale->status === 'completed' ? route('tenant.pos.receipt', $sale) : null,
        ]);
    }

    /**
     * Render thermal receipt view for a completed sale.
     */
    public function receipt(Sale $sale): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_if((int) $sale->tenant_id !== (int) $tenant->id, 403);

        $sale->load(['items.product', 'payments', 'shop', 'register', 'user']);

        return view('tenant.pos.receipt', [
            'tenant' => $tenant,
            'sale' => $sale,
        ]);
    }

    /**
     * Sales history view.
     */
    public function sales(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sales.view'), 403);

        $query = Sale::query()
            ->with(['shop', 'register', 'user', 'payments'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search): void {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->integer('shop_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $sales = $query->paginate(20)->withQueryString();
        $shops = Shop::query()->orderBy('name')->get();

        return view('tenant.pos.sales', [
            'tenant' => $tenant,
            'sales' => $sales,
            'shops' => $shops,
        ]);
    }

    /**
     * Void a sale.
     */
    public function voidSale(Request $request, Sale $sale): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);
        abort_unless($request->user()?->hasPermission('pos.sale.void'), 403);
        abort_if((int) $sale->tenant_id !== (int) $tenant->id, 403);

        $validated = $request->validate([
            'reason' => ['required_without:void_reason', 'string', 'max:255'],
            'void_reason' => ['required_without:reason', 'string', 'max:300'],
        ]);

        $this->saleService->voidSale($sale, $request->user(), $validated['reason'] ?? $validated['void_reason']);

        return redirect()->route('tenant.pos.sales')
            ->with('success', "Sale #{$sale->receipt_number} has been voided.");
    }
}
