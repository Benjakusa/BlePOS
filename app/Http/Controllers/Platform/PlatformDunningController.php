<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformDunningEvent;
use App\Models\PlatformInvoice;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformDunningController extends Controller
{
    public function __construct(
        protected PlatformBillingService $billing,
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.view'), 403);

        $events = PlatformDunningEvent::query()
            ->with(['tenant', 'invoice'])
            ->latest('triggered_at')
            ->paginate(20);

        $overdueInvoices = PlatformInvoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('due_date', '<', now()->toDateString())
            ->where('amount_due', '>', 0)
            ->with(['tenant', 'subscription'])
            ->orderBy('due_date')
            ->get();

        return view('platform.billing.dunning.index', [
            'events' => $events,
            'overdueInvoices' => $overdueInvoices,
        ]);
    }

    public function trigger(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $result = $this->billing->processDunning();

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'platform.dunning.process',
            targetType: PlatformDunningEvent::class,
            targetId: null,
            details: $result,
        );

        return redirect()->route('platform.dunning.index')
            ->with('notice', "Dunning cycle executed: {$result['events_created']} event(s) sent, {$result['subscriptions_past_due']} subscription(s) marked past-due, {$result['tenants_suspended']} tenant(s) suspended.");
    }
}
