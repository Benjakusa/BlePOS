<?php

namespace App\Http\Controllers\Tenant\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymCheckin;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymMember;
use App\Models\GymPayment;
use App\Models\GymPlan;
use App\Models\GymSubscription;
use App\Models\GymTrainer;
use App\Services\ModulePaymentService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GymController extends Controller
{
    public function __construct(private readonly ModulePaymentService $payments) {}

    // Dashboard
    public function index(): View
    {
        $members = GymMember::withoutTrashed()->count();
        $activeSubscriptions = GymSubscription::where('status', 'active')->count();
        $todayCheckins = GymCheckin::whereDate('checked_in_at', today())->count();
        $upcomingClasses = GymClass::where('scheduled_at', '>=', now())->where('is_cancelled', false)->count();

        return view('tenant.gym.index', compact('members', 'activeSubscriptions', 'todayCheckins', 'upcomingClasses'));
    }

    // Members CRUD
    public function members(): View
    {
        return view('tenant.gym.members', ['members' => GymMember::withoutTrashed()->paginate(20)]);
    }

    public function createMember(): View
    {
        return view('tenant.gym.members-create');
    }

    public function storeMember(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'dob' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        GymMember::create($data);

        return redirect()->route('tenant.gym.members')->with('success', 'Member added.');
    }

    public function editMember(GymMember $member): View
    {
        return view('tenant.gym.members-edit', compact('member'));
    }

    public function updateMember(Request $r, GymMember $member): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'dob' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $member->update($data);

        return redirect()->route('tenant.gym.members')->with('success', 'Member updated.');
    }

    // Plans CRUD
    public function plans(): View
    {
        return view('tenant.gym.plans', ['plans' => GymPlan::withoutTrashed()->paginate(20)]);
    }

    public function createPlan(): View
    {
        return view('tenant.gym.plans-create');
    }

    public function storePlan(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'billing_cycle' => 'required|in:monthly,weekly,sessions',
            'price' => 'required|numeric|min:0',
            'session_credits' => 'nullable|integer|min:1',
        ]);
        GymPlan::create($data);

        return redirect()->route('tenant.gym.plans')->with('success', 'Plan created.');
    }

    // Subscriptions
    public function subscriptions(): View
    {
        return view('tenant.gym.subscriptions', [
            'subscriptions' => GymSubscription::with(['member', 'plan'])->paginate(20),
        ]);
    }

    public function createSubscription(): View
    {
        return view('tenant.gym.subscriptions-create', [
            'members' => GymMember::all(),
            'plans' => GymPlan::where('is_active', true)->get(),
        ]);
    }

    public function storeSubscription(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'member_id' => 'required|integer',
            'plan_id' => 'required|integer',
            'start_date' => 'required|date',
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);
        $plan = GymPlan::findOrFail($data['plan_id']);
        $endDate = match ($plan->billing_cycle) {
            'monthly' => Carbon::parse($data['start_date'])->addMonth(),
            'weekly' => Carbon::parse($data['start_date'])->addWeek(),
            'sessions' => Carbon::parse($data['start_date'])->addMonth(),
        };
        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['method'], $data['amount'], 'gym', $data['reference'] ?? null);
        $sub = GymSubscription::create([
            'member_id' => $data['member_id'],
            'plan_id' => $data['plan_id'],
            'status' => 'active',
            'start_date' => $data['start_date'],
            'end_date' => $endDate,
            'session_credits_remaining' => $plan->session_credits,
        ]);
        GymPayment::create([
            'subscription_id' => $sub->id,
            'member_id' => $sub->member_id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'paid_at' => now(),
        ]);

        return redirect()->route('tenant.gym.subscriptions')->with('success', 'Subscription created.');
    }

    // Freeze subscription
    public function freezeSubscription(Request $r, GymSubscription $subscription): RedirectResponse
    {
        abort_if($subscription->status !== 'active', 422, 'Only active subscriptions can be frozen.');
        $data = $r->validate([
            'freeze_start' => 'required|date',
            'freeze_end' => 'required|date|after:freeze_start',
        ]);
        $days = Carbon::parse($data['freeze_start'])->diffInDays(Carbon::parse($data['freeze_end']));
        $subscription->update([
            'status' => 'frozen',
            'freeze_start' => $data['freeze_start'],
            'freeze_end' => $data['freeze_end'],
            'end_date' => Carbon::parse($subscription->end_date)->addDays($days),
        ]);

        return back()->with('success', 'Subscription frozen. End date extended by '.$days.' days.');
    }

    // Renew subscription
    public function renewSubscription(Request $r, GymSubscription $subscription): RedirectResponse
    {
        $data = $r->validate([
            'method' => 'required|in:cash,mpesa',
            'reference' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
        ]);
        $plan = $subscription->plan;
        $newEnd = match ($plan->billing_cycle) {
            'monthly' => Carbon::parse($subscription->end_date)->addMonth(),
            'weekly' => Carbon::parse($subscription->end_date)->addWeek(),
            default => Carbon::parse($subscription->end_date)->addMonth(),
        };
        $tenant = TenantContext::getTenant();
        $payMeta = $this->payments->preparePayment($tenant->id, TenantContext::getShopId(), $data['method'], $data['amount'], 'gym', $data['reference'] ?? null);
        $subscription->update(['status' => 'active', 'end_date' => $newEnd, 'session_credits_remaining' => $plan->session_credits]);
        GymPayment::create([
            'subscription_id' => $subscription->id,
            'member_id' => $subscription->member_id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'payment_meta' => $payMeta,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Subscription renewed to '.Carbon::parse($newEnd)->toDateString());
    }

    // Check-in
    public function checkin(): View
    {
        return view('tenant.gym.checkin', [
            'recentCheckins' => GymCheckin::with('member')->latest()->limit(20)->get(),
        ]);
    }

    public function processCheckin(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'member_id' => 'required|integer',
            'method' => 'required|in:manual,barcode,qr',
        ]);
        $sub = GymSubscription::where('member_id', $data['member_id'])
            ->whereIn('status', ['active', 'frozen'])
            ->latest()
            ->first();
        if (! $sub) {
            return back()->withErrors(['member_id' => 'No active subscription found.']);
        }
        if ($sub->end_date < today()) {
            $sub->update(['status' => 'expired']);

            return back()->withErrors(['member_id' => 'Subscription expired on '.$sub->end_date.'.']);
        }
        if ($sub->status === 'frozen') {
            return back()->withErrors(['member_id' => 'Subscription is frozen until '.$sub->freeze_end.'.']);
        }
        GymCheckin::create(['member_id' => $data['member_id'], 'checked_in_at' => now(), 'method' => $data['method']]);

        return back()->with('success', 'Check-in recorded.');
    }

    // Classes
    public function classes(): View
    {
        return view('tenant.gym.classes', ['classes' => GymClass::with('trainer')->paginate(20)]);
    }

    public function createClass(): View
    {
        return view('tenant.gym.classes-create', ['trainers' => GymTrainer::where('is_active', true)->get()]);
    }

    public function storeClass(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'trainer_id' => 'nullable|integer',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
        ]);
        GymClass::create($data);

        return redirect()->route('tenant.gym.classes')->with('success', 'Class scheduled.');
    }

    // Book a class
    public function bookClass(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'class_id' => 'required|integer',
            'member_id' => 'required|integer',
        ]);
        $class = GymClass::findOrFail($data['class_id']);
        abort_if($class->is_cancelled, 422, 'Class is cancelled.');
        $confirmedCount = GymClassBooking::where('class_id', $class->id)->where('status', 'confirmed')->count();
        $status = $confirmedCount >= $class->capacity ? 'waitlisted' : 'confirmed';
        GymClassBooking::create(['class_id' => $class->id, 'member_id' => $data['member_id'], 'status' => $status]);

        return back()->with('success', 'Booking '.$status.'.');
    }

    // Mark attendance (deducts session credit)
    public function markAttendance(GymClassBooking $booking): RedirectResponse
    {
        $sub = GymSubscription::where('member_id', $booking->member_id)->where('status', 'active')->latest()->first();
        if ($sub && $sub->plan && $sub->plan->billing_cycle === 'sessions') {
            if (($sub->session_credits_remaining ?? 0) <= 0) {
                return back()->withErrors(['error' => 'No session credits remaining.']);
            }
            $sub->decrement('session_credits_remaining');
        }
        $booking->update(['status' => 'attended', 'attended_at' => now()]);

        return back()->with('success', 'Attendance marked.');
    }

    // Trainers
    public function trainers(): View
    {
        return view('tenant.gym.trainers', ['trainers' => GymTrainer::withoutTrashed()->paginate(20)]);
    }

    public function createTrainer(): View
    {
        return view('tenant.gym.trainers-create');
    }

    public function storeTrainer(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);
        GymTrainer::create($data);

        return redirect()->route('tenant.gym.trainers')->with('success', 'Trainer added.');
    }
}
