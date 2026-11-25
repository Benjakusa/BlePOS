<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Models\SupportCase;
use App\Models\Tenant;
use App\Services\PlatformAuditRecorder;
use App\Services\SupportService;
use App\Services\WorkspaceNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSupportCaseController extends Controller
{
    public function __construct(
        protected SupportService $support,
        protected PlatformAuditRecorder $audit,
        protected WorkspaceNotificationService $notifications
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $query = SupportCase::query()->with(['tenant', 'shop', 'assignedAgent'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('assigned_agent_id');
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }

        $cases = $query->paginate(25)->withQueryString();
        $summary = $this->support->getSupportSummary();

        return view('platform.support.cases.index', [
            'cases' => $cases,
            'summary' => $summary,
            'selectedStatus' => $request->query('status', ''),
            'selectedPriority' => $request->query('priority', ''),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $tenants = Tenant::query()->orderBy('name')->get();
        $agents = PlatformUser::query()->where('status', 'active')->orderBy('name')->get();

        return view('platform.support.cases.create', [
            'tenants' => $tenants,
            'agents' => $agents,
            'selectedTenantId' => $request->query('tenant_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'assigned_agent_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'requester_name' => ['required', 'string', 'max:128'],
            'requester_email' => ['required', 'email', 'max:128'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'in:billing,hardware_pos,inventory,user_access,etims_tax,system_bug,general'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);
        $assignedAgent = ! empty($validated['assigned_agent_id'])
            ? PlatformUser::query()->find($validated['assigned_agent_id'])
            : null;

        $case = $this->support->createCase(
            tenant: $tenant,
            data: $validated,
            assignedAgent: $assignedAgent,
        );
        $this->notifications->tenantUsers($tenant->id, [
            'title' => 'A support ticket was opened for your tenant',
            'message' => "{$case->case_number}: {$case->subject}",
            'url' => route('tenant.support.cases.show', $case),
            'kind' => 'support',
        ]);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'support.case.create',
            targetType: SupportCase::class,
            targetId: (string) $case->id,
            details: [
                'case_number' => $case->case_number,
                'tenant_id' => $tenant->id,
                'priority' => $case->priority,
                'category' => $case->category,
            ],
        );

        return redirect()->route('platform.support.cases.show', $case)
            ->with('notice', "Support case {$case->case_number} opened successfully.");
    }

    public function show(Request $request, SupportCase $case): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $case->load(['tenant.subscriptions.plan', 'shop', 'assignedAgent', 'notes' => fn ($q) => $q->latest()]);
        $agents = PlatformUser::query()->where('status', 'active')->orderBy('name')->get();

        return view('platform.support.cases.show', [
            'case' => $case,
            'agents' => $agents,
        ]);
    }

    public function assign(Request $request, SupportCase $case): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $validated = $request->validate([
            'agent_id' => ['nullable', 'integer', 'exists:platform_users,id'],
        ]);

        $agent = ! empty($validated['agent_id']) ? PlatformUser::query()->find($validated['agent_id']) : null;
        $this->support->assignCase($case, $agent, $request->user('platform'));

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'support.case.assign',
            targetType: SupportCase::class,
            targetId: (string) $case->id,
            details: [
                'case_number' => $case->case_number,
                'assigned_agent_id' => $agent?->id,
                'assigned_agent_name' => $agent?->name ?? 'Unassigned',
            ],
        );

        return redirect()->route('platform.support.cases.show', $case)
            ->with('notice', 'Case assignment updated.');
    }

    public function updateStatus(Request $request, SupportCase $case): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,waiting_on_tenant,waiting_on_platform,resolved,closed'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->support->updateStatus(
            case: $case,
            newStatus: $validated['status'],
            resolutionNotes: $validated['resolution_notes'] ?? null,
            actor: $request->user('platform'),
        );
        $this->notifications->tenantUsers($case->tenant_id, [
            'title' => 'Support ticket status updated',
            'message' => "{$case->case_number} is now ".str_replace('_', ' ', $validated['status']).'.',
            'url' => route('tenant.support.cases.show', $case),
            'kind' => 'support',
        ]);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'support.case.status',
            targetType: SupportCase::class,
            targetId: (string) $case->id,
            details: [
                'case_number' => $case->case_number,
                'new_status' => $validated['status'],
            ],
        );

        return redirect()->route('platform.support.cases.show', $case)
            ->with('notice', "Case status updated to {$validated['status']}.");
    }

    public function addNote(Request $request, SupportCase $case): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'is_internal' => ['required', 'boolean'],
        ]);

        $this->support->addNote(
            case: $case,
            content: $validated['content'],
            isInternal: (bool) $validated['is_internal'],
            author: $request->user('platform'),
        );
        if (! (bool) $validated['is_internal']) {
            $this->notifications->caseReply($case, false);
        }

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'support.case.note',
            targetType: SupportCase::class,
            targetId: (string) $case->id,
            details: [
                'case_number' => $case->case_number,
                'is_internal' => (bool) $validated['is_internal'],
            ],
        );

        return redirect()->route('platform.support.cases.show', $case)
            ->with('notice', 'Note added to case history.');
    }
}
