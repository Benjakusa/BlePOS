<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SupportCase;
use App\Services\SupportService;
use App\Services\TenantContext;
use App\Services\WorkspaceNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSupportCaseController extends Controller
{
    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant, 403);
        $cases = SupportCase::query()->where('tenant_id', $tenant->id)->latest()->paginate(20);

        return view('tenant.support.index', compact('cases'));
    }

    public function create(): View
    {
        return view('tenant.support.create');
    }

    public function store(Request $request, SupportService $support, WorkspaceNotificationService $notifications): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant, 403);
        $user = $request->user();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', 'in:billing,hardware_pos,inventory,user_access,etims_tax,system_bug,general'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
        ]);
        $case = $support->createCase($tenant, $data + [
            'requester_name' => $user->name,
            'requester_email' => $user->email,
        ]);
        $case->update(['requester_user_id' => $user->id]);
        $notifications->caseCreated($case);

        return redirect()->route('tenant.support.cases.show', $case)->with('success', "Ticket {$case->case_number} created.");
    }

    public function show(Request $request, int $case): View
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant, 403);
        $case = SupportCase::query()->where('tenant_id', $tenant->id)->whereKey($case)->firstOrFail();
        $case->load(['notes' => fn ($query) => $query->where('is_internal', false)->latest()]);

        return view('tenant.support.show', compact('case'));
    }

    public function reply(Request $request, int $case, WorkspaceNotificationService $notifications): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if(! $tenant, 403);
        $case = SupportCase::query()->where('tenant_id', $tenant->id)->whereKey($case)->firstOrFail();
        abort_if(in_array($case->status, ['resolved', 'closed'], true), 422, 'This ticket is closed.');
        $data = $request->validate(['content' => ['required', 'string', 'max:2000']]);
        $user = $request->user();
        $case->notes()->create([
            'tenant_user_id' => $user->id,
            'author_name' => $user->name,
            'content' => $data['content'],
            'is_internal' => false,
        ]);
        $case->update(['status' => 'waiting_on_platform']);
        $notifications->caseReply($case, true);

        return redirect()->route('tenant.support.cases.show', $case)->with('success', 'Reply sent to the support team.');
    }
}
