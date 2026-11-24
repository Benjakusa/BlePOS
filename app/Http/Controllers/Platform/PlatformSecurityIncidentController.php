<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSecurityIncident;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSecurityIncidentController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:new,acknowledged,investigating,escalated,resolved,closed'],
            'severity' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'category' => ['nullable', 'string'],
        ]);

        $incidents = PlatformSecurityIncident::query()
            ->with(['assignedOfficer:id,name,email', 'reporter:id,name,email', 'tenant:id,name,subdomain'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('platform.security.incidents.index', [
            'incidents' => $incidents,
            'filters' => $filters,
        ]);
    }

    public function create(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        return view('platform.security.incidents.create', [
            'officers' => PlatformUser::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
            'tenants' => Tenant::orderBy('name')->get(['id', 'name', 'subdomain']),
        ]);
    }

    public function store(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:brute_force,mfa_abuse,credential_stuffing,unauthorized_access,data_exfiltration,api_abuse,general'],
            'severity' => ['required', 'string', 'in:low,medium,high,critical'],
            'platform_user_id' => ['nullable', 'exists:platform_users,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'description' => ['required', 'string'],
            'containment_steps' => ['nullable', 'string'],
            'initial_note' => ['nullable', 'string'],
        ]);

        $incident = $securityService->createIncident($validated, $request->user('platform'));

        return redirect()->route('platform.security.incidents.show', $incident)
            ->with('notice', "Security Incident {$incident->incident_number} recorded successfully.");
    }

    public function show(
        Request $request,
        PlatformSecurityIncident $incident,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $incident->load([
            'assignedOfficer',
            'reporter',
            'tenant',
            'notes.user',
        ]);

        return view('platform.security.incidents.show', [
            'incident' => $incident,
            'officers' => PlatformUser::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function updateStatus(
        Request $request,
        PlatformSecurityIncident $incident,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:new,acknowledged,investigating,escalated,resolved,closed'],
            'notes' => ['nullable', 'string'],
        ]);

        $securityService->updateIncidentStatus(
            $incident,
            $validated['status'],
            $validated['notes'] ?? null,
            $request->user('platform')
        );

        return redirect()->route('platform.security.incidents.show', $incident)
            ->with('notice', "Incident status updated to {$validated['status']}.");
    }

    public function addNote(
        Request $request,
        PlatformSecurityIncident $incident,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $validated = $request->validate([
            'note' => ['required', 'string'],
            'is_containment_action' => ['nullable', 'boolean'],
        ]);

        $securityService->addIncidentNote(
            $incident,
            $validated['note'],
            (bool) ($validated['is_containment_action'] ?? false),
            $request->user('platform')
        );

        return redirect()->route('platform.security.incidents.show', $incident)
            ->with('notice', 'Incident note added.');
    }
}
