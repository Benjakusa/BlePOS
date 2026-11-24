<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformDataProtectionRequest;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Services\ComplianceService;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformDataProtectionController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,under_review,processing,completed,rejected'],
            'request_type' => ['nullable', 'string', 'in:access_export,rectification,erasure_deletion,objection,consent_withdrawal'],
        ]);

        $requests = PlatformDataProtectionRequest::query()
            ->with(['tenant:id,name,subdomain', 'assignedOfficer:id,name,email'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['request_type'] ?? null, fn ($query, $type) => $query->where('request_type', $type))
            ->orderBy('due_date')
            ->paginate(25)
            ->withQueryString();

        return view('platform.compliance.dpa.index', [
            'requests' => $requests,
            'filters' => $filters,
        ]);
    }

    public function create(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        return view('platform.compliance.dpa.create', [
            'tenants' => Tenant::orderBy('name')->get(['id', 'name', 'subdomain']),
            'officers' => PlatformUser::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $validated = $request->validate([
            'subject_name' => ['required', 'string', 'max:255'],
            'subject_email' => ['required', 'email', 'max:255'],
            'subject_phone' => ['nullable', 'string', 'max:30'],
            'request_type' => ['required', 'string', 'in:access_export,rectification,erasure_deletion,objection,consent_withdrawal'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'legal_basis' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'assigned_officer_id' => ['nullable', 'exists:platform_users,id'],
        ]);

        $dpa = $complianceService->createDataProtectionRequest($validated, $request->user('platform'));

        return redirect()->route('platform.compliance.dpa.show', $dpa)
            ->with('notice', "Data Protection Request {$dpa->request_number} recorded successfully.");
    }

    public function show(
        Request $request,
        PlatformDataProtectionRequest $dpa,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $dpa->load(['tenant', 'assignedOfficer']);

        return view('platform.compliance.dpa.show', [
            'dpa' => $dpa,
        ]);
    }

    public function updateStatus(
        Request $request,
        PlatformDataProtectionRequest $dpa,
        PlatformPermissionAuthorizer $authorizer,
        ComplianceService $complianceService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.compliance.view');

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,under_review,processing,completed,rejected'],
            'resolution_summary' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ]);

        $complianceService->updateDataProtectionRequestStatus(
            $dpa,
            $validated['status'],
            $validated['resolution_summary'] ?? null,
            $validated['rejection_reason'] ?? null,
            $request->user('platform')
        );

        return redirect()->route('platform.compliance.dpa.show', $dpa)
            ->with('notice', "Data Protection Request {$dpa->request_number} status updated to {$validated['status']}.");
    }
}
